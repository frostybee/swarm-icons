<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\Util\ManifestManager;
use Frostybee\SwarmIcons\Util\NpmDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Checks for newer versions of downloaded JSON icon sets on npm and updates them.
 */
class JsonUpdateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('json:update')
            ->setDescription('Check for and apply updates to downloaded JSON icon sets')
            ->addArgument(
                'sets',
                InputArgument::IS_ARRAY,
                'Specific prefixes to update (default: all downloaded sets)',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Check for updates without downloading',
            )
            ->addOption(
                'dest',
                'd',
                InputOption::VALUE_REQUIRED,
                'JSON files directory (auto-detected if not specified)',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>json:update</info> command checks for newer versions of downloaded JSON icon sets.

                    Check all downloaded sets for updates:

                        <info>php bin/swarm-icons json:update --dry-run</info>

                    Update all downloaded sets:

                        <info>php bin/swarm-icons json:update</info>

                    Update specific sets:

                        <info>php bin/swarm-icons json:update mdi tabler</info>
                    HELP,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifest = new ManifestManager();
        $downloader = new NpmDownloader();

        // 1. Load manifest
        $allPrefixes = $manifest->loadPrefixes();
        if ($allPrefixes === null) {
            $io->error(
                'No swarm-icons.json manifest found. Download sets first with: json:download',
            );

            return Command::FAILURE;
        }

        // 2. Determine scope
        /** @var list<string> $requestedSets */
        $requestedSets = $input->getArgument('sets');
        $sets = $requestedSets !== [] ? $requestedSets : $allPrefixes;

        // Validate requested sets exist in manifest
        $validSets = [];
        foreach ($sets as $prefix) {
            if (!\in_array($prefix, $allPrefixes, true)) {
                $io->warning("'{$prefix}' is not in the manifest. Skipping.");
                continue;
            }
            $validSets[] = $prefix;
        }

        if ($validSets === []) {
            $io->error('No valid sets to check.');

            return Command::FAILURE;
        }

        // 3. Resolve destination
        /** @var string|null $destOption */
        $destOption = $input->getOption('dest');
        $destDir = $destOption ?? $manifest->resolveJsonDirectory();
        if ($destDir === null) {
            $io->error(
                'Could not auto-detect the JSON resources directory. '
                . 'Use --dest to specify the destination path.',
            );

            return Command::FAILURE;
        }

        // 4. Load stored versions
        $storedVersions = $manifest->loadVersions();

        // 5. Check each set for updates
        $io->title('Checking for Updates');
        $io->text('Checking ' . \count($validSets) . ' set(s) against npm...');
        $io->newLine();

        /** @var list<array{prefix: string, current: string, latest: string, needsUpdate: bool, size: int|null}> $results */
        $results = [];

        foreach ($validSets as $prefix) {
            $meta = $downloader->fetchVersionMetadata('@iconify-json/' . $prefix);
            if ($meta === null) {
                $results[] = [
                    'prefix' => $prefix,
                    'current' => $storedVersions[$prefix] ?? '?',
                    'latest' => 'error',
                    'needsUpdate' => false,
                    'size' => null,
                ];
                continue;
            }

            $current = $storedVersions[$prefix] ?? null;
            $needsUpdate = $current === null || $current !== $meta['version'];

            $results[] = [
                'prefix' => $prefix,
                'current' => $current ?? 'unknown',
                'latest' => $meta['version'],
                'needsUpdate' => $needsUpdate,
                'size' => $meta['unpackedSize'],
            ];
        }

        // 6. Display table
        $rows = [];
        foreach ($results as $r) {
            $status = match (true) {
                $r['latest'] === 'error' => '<error> fetch failed </error>',
                !$r['needsUpdate'] => '<info>up to date</info>',
                $r['current'] === 'unknown' => '<comment>unknown version</comment>',
                default => '<fg=yellow>update available</>',
            };
            $rows[] = [$r['prefix'], $r['current'], $r['latest'], $status];
        }

        $io->table(['Prefix', 'Current', 'Latest', 'Status'], $rows);

        $updatable = array_filter($results, fn(array $r): bool => $r['needsUpdate']);
        if ($updatable === []) {
            $io->success('All sets are up to date.');

            return Command::SUCCESS;
        }

        $io->text(\count($updatable) . ' set(s) have updates available.');

        // 7. Dry-run stops here
        if ($input->getOption('dry-run')) {
            return Command::SUCCESS;
        }

        // 8. Download updates
        $io->newLine();
        $updated = 0;
        $failed = 0;

        /** @var array<string, string> $newVersions */
        $newVersions = [];

        foreach ($updatable as $r) {
            $prefix = $r['prefix'];
            $version = $r['latest'];
            $package = '@iconify-json/' . $prefix;

            $io->section("Updating {$prefix} to {$version}");

            $io->text('Downloading tarball...');
            $tarball = $downloader->downloadTarball($package, $version);
            if ($tarball === null) {
                $io->warning("Download failed for {$prefix}. Skipping.");
                $failed++;
                continue;
            }

            $io->text('Extracting icons.json...');
            $content = $downloader->extractFile($tarball, 'icons.json');
            if ($content === null) {
                $io->warning("Could not extract icons.json for {$prefix}. Skipping.");
                $failed++;
                continue;
            }

            $destFile = $destDir . '/' . $prefix . '.json';
            file_put_contents($destFile, $content);
            $sizeKb = number_format(\strlen($content) / 1024, 1);
            $io->text("  Wrote {$destFile} ({$sizeKb} KB)");

            $newVersions[$prefix] = $version;
            $updated++;
        }

        // 9. Update manifest with new versions
        if ($updated > 0) {
            $manifest->save($allPrefixes, $newVersions);
        }

        // 10. Summary
        $io->newLine();
        $upToDate = \count($results) - \count($updatable);
        if ($failed > 0) {
            $io->warning("Updated {$updated}, {$upToDate} already current, {$failed} failed.");
        } else {
            $io->success("Updated {$updated} set(s). {$upToDate} already up to date.");
        }

        return $failed > 0 && $updated === 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
