<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

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
 * Each JSON set is downloaded from the @iconify-json/{prefix} npm package
 * and placed into the swarm-icons-json package's resources/json/ directory.
 */
class JsonDownloadCommand extends Command
{
    /**
     * Available JSON icon sets: key => npm package name.
     *
     * @var array<string, string>
     */
    private const SETS = [
        'mdi' => '@iconify-json/mdi',
        'fa-solid' => '@iconify-json/fa-solid',
        'fa-regular' => '@iconify-json/fa-regular',
        'fa-brands' => '@iconify-json/fa-brands',
        'carbon' => '@iconify-json/carbon',
        'octicon' => '@iconify-json/octicon',
        'fluent' => '@iconify-json/fluent',
        'ion' => '@iconify-json/ion',
        'ri' => '@iconify-json/ri',
        'iconoir' => '@iconify-json/iconoir',
        'mingcute' => '@iconify-json/mingcute',
        'solar' => '@iconify-json/solar',
        'uil' => '@iconify-json/uil',
        'bx' => '@iconify-json/bx',
        'line-md' => '@iconify-json/line-md',
        'tabler' => '@iconify-json/tabler',
        'heroicons' => '@iconify-json/heroicons',
        'lucide' => '@iconify-json/lucide',
        'bi' => '@iconify-json/bi',
        'ph' => '@iconify-json/ph',
        'simple-icons' => '@iconify-json/simple-icons',
    ];

    protected function configure(): void
    {
        $this
            ->setName('json:download')
            ->setDescription('Download Iconify JSON collection files from npm')
            ->addArgument(
                'sets',
                InputArgument::IS_ARRAY,
                'Icon set prefixes to download (e.g., mdi fa-solid fa-brands)',
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Download all available JSON icon sets',
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

                    List available sets (when no manifest exists):

                        <info>php bin/swarm-icons json:download</info>

                    Download specific sets:

                        <info>php bin/swarm-icons json:download mdi fa-solid fa-brands</info>

                    Download all sets:

                        <info>php bin/swarm-icons json:download --all</info>

                    Custom destination:

                        <info>php bin/swarm-icons json:download mdi --dest=/path/to/json</info>

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

        /** @var list<string> $sets */
        $sets = $input->getArgument('sets');
        $downloadAll = $input->getOption('all');
        /** @var string|null $destOption */
        $destOption = $input->getOption('dest');

        // No args and no --all: restore from manifest or list available sets
        if (empty($sets) && !$downloadAll) {
            $manifest = $this->loadManifest();
            if ($manifest !== null) {
                $sets = $manifest;
            } else {
                return $this->listSets($io);
            }
        }

        // Determine which sets to download
        if ($downloadAll) {
            $selected = array_keys(self::SETS);
        } else {
            // Validate set names
            foreach ($sets as $set) {
                if (!isset(self::SETS[$set])) {
                    $io->error("Unknown icon set: '{$set}'. Run without arguments to see available sets.");

                    return Command::FAILURE;
                }
            }
            $selected = $sets;
        }

        // Resolve destination directory
        $destDir = $destOption ?? $this->resolveDestination();
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

        foreach ($selected as $prefix) {
            $package = self::SETS[$prefix];
            $io->section("{$prefix} ({$package})");

            // Fetch latest version
            $io->text('Fetching latest version...');
            $version = $downloader->fetchLatestVersion($package);
            if ($version === null) {
                $io->warning("Could not determine latest version. Skipping.");
                $failed++;
                continue;
            }
            $io->text("  Version: {$version}");

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
            $succeeded++;
        }

        $io->newLine();

        // Save manifest so future no-args invocations can restore these sets
        if ($succeeded > 0) {
            $this->saveManifest($selected);
        }

        if ($failed > 0) {
            $io->warning("Downloaded {$succeeded} set(s), {$failed} failed.");

            return $succeeded > 0 ? Command::SUCCESS : Command::FAILURE;
        }

        $io->success("Downloaded {$succeeded} JSON icon set(s) to {$destDir}");

        return Command::SUCCESS;
    }

    /**
     * List all available sets with their download status.
     */
    private function listSets(SymfonyStyle $io): int
    {
        $io->title('Available JSON Icon Sets');

        $destDir = $this->resolveDestination();

        $rows = [];
        foreach (self::SETS as $prefix => $package) {
            $installed = $destDir !== null && file_exists($destDir . '/' . $prefix . '.json');
            $status = $installed ? '<info>downloaded</info>' : '<comment>not downloaded</comment>';
            $rows[] = [$prefix, $package, $status];
        }

        $io->table(['Prefix', 'npm Package', 'Status'], $rows);
        $io->text('Download sets with: <info>php bin/swarm-icons json:download mdi fa-solid</info>');
        $io->text('Download all with:  <info>php bin/swarm-icons json:download --all</info>');

        return Command::SUCCESS;
    }

    /**
     * Auto-detect the JSON resources directory.
     *
     * Resolves to resources/json/ relative to the core package root.
     * When installed as a dependency, checks the vendor path first.
     */
    private function resolveDestination(): ?string
    {
        // Core package: src/Command/ → up 2 levels → resources/json/
        $corePath = \dirname(__DIR__, 2) . '/resources/json';
        if (is_dir(\dirname($corePath))) {
            return $corePath;
        }

        // Installed as vendor dependency
        $vendorPath = \dirname(__DIR__, 3) . '/frostybee/swarm-icons/resources/json';
        if (is_dir(\dirname($vendorPath))) {
            return $vendorPath;
        }

        return null;
    }

    /**
     * Resolve the path to the manifest file in the project root.
     */
    private function resolveManifestPath(): string
    {
        return getcwd() . '/swarm-icons.json';
    }

    /**
     * Save downloaded set prefixes to the manifest file.
     *
     * Merges with any existing manifest entries so incremental
     * downloads accumulate (e.g., `json:download mdi` then `json:download bi`).
     *
     * @param list<string> $prefixes
     */
    private function saveManifest(array $prefixes): void
    {
        $path = $this->resolveManifestPath();

        // Merge with existing manifest
        $existing = $this->loadManifest() ?? [];
        $merged = array_values(array_unique(array_merge($existing, $prefixes)));
        sort($merged);

        $data = ['json-sets' => $merged];
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    /**
     * Load set prefixes from the manifest file.
     *
     * @return list<string>|null Null if no manifest exists.
     */
    private function loadManifest(): ?array
    {
        $path = $this->resolveManifestPath();
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!\is_array($data) || !isset($data['json-sets']) || !\is_array($data['json-sets'])) {
            return null;
        }

        /** @var list<string> $sets */
        $sets = $data['json-sets'];

        // Filter out any prefixes that are no longer valid
        $valid = array_filter(
            $sets,
            static fn(string $prefix): bool => isset(self::SETS[$prefix]),
        );

        return $valid !== [] ? array_values($valid) : null;
    }
}
