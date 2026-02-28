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
 * Downloads Iconify JSON collection files from npm for use with JsonCollectionProvider.
 *
 * Any valid Iconify prefix can be downloaded — the npm package is resolved
 * as @iconify-json/{prefix}. Popular sets are listed for convenience.
 */
class JsonDownloadCommand extends Command
{
    /**
     * Popular/recommended icon sets shown in the listing.
     *
     * Any Iconify prefix works for download — this list is only for display and --all.
     *
     * @var list<string>
     */
    private const POPULAR_SETS = [
        'bi',
        'bx',
        'carbon',
        'fa7-brands',
        'fa7-regular',
        'fa7-solid',
        'flowbite',
        'fluent',
        'heroicons',
        'icon-park-outline',
        'iconoir',
        'ion',
        'line-md',
        'lucide',
        'mdi',
        'mingcute',
        'octicon',
        'ph',
        'ri',
        'simple-icons',
        'solar',
        'tabler',
        'uil',
    ];

    protected function configure(): void
    {
        $this
            ->setName('json:download')
            ->setDescription('Download Iconify JSON collection files from npm')
            ->addArgument(
                'sets',
                InputArgument::IS_ARRAY,
                'Icon set prefixes to download (any valid Iconify prefix, e.g., mdi fluent-emoji noto)',
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Download all popular JSON icon sets',
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'List popular sets with download status without downloading',
            )
            ->addOption(
                'dest',
                'd',
                InputOption::VALUE_REQUIRED,
                'Destination directory for JSON files (auto-detected if not specified)',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>json:download</info> command downloads Iconify JSON collection files from npm.

                    List popular sets with download status:

                        <info>php bin/swarm-icons json:download --list</info>

                    Download specific sets (any valid Iconify prefix):

                        <info>php bin/swarm-icons json:download mdi fa-solid fluent-emoji</info>

                    Download all popular sets:

                        <info>php bin/swarm-icons json:download --all</info>

                    Custom destination:

                        <info>php bin/swarm-icons json:download mdi --dest=/path/to/json</info>

                    Browse all 200+ available Iconify icon sets:

                        <info>php bin/swarm-icons json:browse</info>
                        <info>php bin/swarm-icons json:browse --search=arrow</info>

                    Re-download previously downloaded sets (reads from <comment>swarm-icons.json</comment> manifest):

                        <info>php bin/swarm-icons json:download</info>

                    The manifest is automatically created/updated when you download sets.
                    Add this to your <comment>composer.json</comment> to auto-restore after <info>composer install</info>:

                        <info>"scripts": { "post-install-cmd": ["swarm-icons json:download"] }</info>
                    HELP,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifest = new ManifestManager();

        // Handle --list before anything else
        if ($input->getOption('list')) {
            return $this->listSets($io, $manifest);
        }

        /** @var list<string> $sets */
        $sets = $input->getArgument('sets');
        $downloadAll = $input->getOption('all');
        /** @var string|null $destOption */
        $destOption = $input->getOption('dest');

        // No args and no --all: restore from manifest or list available sets
        if (empty($sets) && !$downloadAll) {
            $restored = $manifest->loadPrefixes();
            if ($restored !== null) {
                $sets = $restored;
            } else {
                return $this->listSets($io, $manifest);
            }
        }

        // Determine which sets to download
        if ($downloadAll) {
            $selected = self::POPULAR_SETS;
        } else {
            $selected = $sets;
        }

        // Resolve destination directory
        $destDir = $destOption ?? $manifest->resolveJsonDirectory();
        if ($destDir === null) {
            $io->error(
                'Could not auto-detect the JSON resources directory. '
                . 'Use --dest to specify the destination path.',
            );

            return Command::FAILURE;
        }

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0o755, true);
        }

        $io->title('Downloading JSON Icon Sets');
        $io->text("Destination: {$destDir}");
        $io->text('Sets to download: ' . \count($selected));
        $io->newLine();

        $downloader = new NpmDownloader();
        $succeeded = 0;
        $failed = 0;

        /** @var array<string, string> $downloadedVersions */
        $downloadedVersions = [];

        foreach ($selected as $prefix) {
            $package = '@iconify-json/' . $prefix;
            $io->section("{$prefix} ({$package})");

            // Fetch latest version and package size
            $io->text('Fetching latest version...');
            $meta = $downloader->fetchVersionMetadata($package);
            if ($meta === null) {
                $io->warning("Could not fetch '{$package}' from npm. Check the prefix and try again.");
                $failed++;
                continue;
            }
            $version = $meta['version'];
            $sizeInfo = $meta['unpackedSize'] !== null
                ? ' (' . $this->formatBytes($meta['unpackedSize']) . ')'
                : '';
            $io->text("  Version: {$version}{$sizeInfo}");

            // Download tarball
            $io->text('Downloading tarball...');
            $tarball = $downloader->downloadTarball($package, $version);
            if ($tarball === null) {
                $io->warning("Download failed. Skipping.");
                $failed++;
                continue;
            }

            // Extract icons.json
            $io->text('Extracting icons.json...');
            $content = $downloader->extractFile($tarball, 'icons.json');
            if ($content === null) {
                $io->warning("Could not extract icons.json from tarball. Skipping.");
                $failed++;
                continue;
            }

            // Write to destination
            $destFile = $destDir . '/' . $prefix . '.json';
            file_put_contents($destFile, $content);
            $sizeKb = number_format(\strlen($content) / 1024, 1);
            $io->text("  Wrote {$destFile} ({$sizeKb} KB)");
            $downloadedVersions[$prefix] = $version;
            $succeeded++;
        }

        $io->newLine();

        // Save manifest so future no-args invocations can restore these sets
        if ($succeeded > 0) {
            $manifest->save($selected, $downloadedVersions);
        }

        if ($failed > 0) {
            $io->warning("Downloaded {$succeeded} set(s), {$failed} failed.");

            return $succeeded > 0 ? Command::SUCCESS : Command::FAILURE;
        }

        $io->success("Downloaded {$succeeded} JSON icon set(s) to {$destDir}");

        return Command::SUCCESS;
    }

    /**
     * List popular sets with their download status.
     */
    private function listSets(SymfonyStyle $io, ManifestManager $manifest): int
    {
        $io->title('Popular JSON Icon Sets');

        $destDir = $manifest->resolveJsonDirectory();

        $rows = [];
        foreach (self::POPULAR_SETS as $prefix) {
            $package = '@iconify-json/' . $prefix;
            $installed = $destDir !== null && file_exists($destDir . '/' . $prefix . '.json');
            $status = $installed ? '<info>downloaded</info>' : '<comment>not downloaded</comment>';
            $rows[] = [$prefix, $package, $status];
        }

        $io->table(['Prefix', 'npm Package', 'Status'], $rows);
        $io->text('Download sets with:  <info>php bin/swarm-icons json:download mdi fa-solid</info>');
        $io->text('Download all with:   <info>php bin/swarm-icons json:download --all</info>');
        $io->text('Browse all 200+ sets: <info>php bin/swarm-icons json:browse</info>');

        return Command::SUCCESS;
    }

    /**
     * Format a byte count into a human-readable string.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 1) . ' MB';
        }

        return number_format($bytes / 1024, 1) . ' KB';
    }
}
