<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\Cache\NullCache;
use Frostybee\SwarmIcons\Provider\JsonCollectionProvider;
use Frostybee\SwarmIcons\Util\ManifestManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Exports icons from a JSON collection as individual SVG files.
 *
 * Useful for designers who need standalone SVG files from an Iconify JSON set.
 */
class IconExportCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('icon:export')
            ->setDescription('Export icons from a JSON collection as individual SVG files')
            ->addArgument(
                'prefix',
                InputArgument::REQUIRED,
                'Icon set prefix (e.g., mdi, tabler)',
            )
            ->addArgument(
                'icons',
                InputArgument::IS_ARRAY,
                'Specific icon names to export (omit for --all)',
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all icons from the set',
            )
            ->addOption(
                'dest',
                'd',
                InputOption::VALUE_REQUIRED,
                'Output directory (default: ./export/{prefix}/)',
            )
            ->addOption(
                'overwrite',
                null,
                InputOption::VALUE_NONE,
                'Overwrite existing SVG files',
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_REQUIRED,
                'Custom path to JSON collection file',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>icon:export</info> command exports icons from a JSON collection as individual SVG files.

                    Export specific icons:

                        <info>php bin/swarm-icons icon:export tabler home star arrow-left</info>

                    Export all icons from a set:

                        <info>php bin/swarm-icons icon:export mdi --all</info>

                    Export to a custom directory:

                        <info>php bin/swarm-icons icon:export tabler home --dest=./my-icons</info>

                    Export from a custom JSON file:

                        <info>php bin/swarm-icons icon:export custom --all --json=/path/to/icons.json</info>

                    Overwrite existing files:

                        <info>php bin/swarm-icons icon:export tabler home --overwrite</info>
                    HELP,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $prefix */
        $prefix = $input->getArgument('prefix');
        /** @var list<string> $iconNames */
        $iconNames = $input->getArgument('icons');
        $exportAll = $input->getOption('all');
        $overwrite = $input->getOption('overwrite');
        /** @var string|null $jsonOption */
        $jsonOption = $input->getOption('json');

        // 1. Resolve JSON file
        $jsonPath = $this->resolveJsonPath($prefix, $jsonOption);
        if ($jsonPath === null) {
            $io->error(
                'Could not auto-detect the JSON directory. Use --json to specify the file path.',
            );

            return Command::FAILURE;
        }

        if (!file_exists($jsonPath)) {
            $io->error("JSON file not found: {$jsonPath}");
            $io->text(
                'Download it first: <info>php bin/swarm-icons json:download ' . $prefix . '</info>',
            );

            return Command::FAILURE;
        }

        // 2. Load provider
        $provider = new JsonCollectionProvider($jsonPath, new NullCache());

        // 3. Determine icons to export
        if ($exportAll) {
            $names = iterator_to_array($provider->all());
        } elseif ($iconNames !== []) {
            $names = [];
            foreach ($iconNames as $name) {
                if (!$provider->has($name)) {
                    $io->warning("Icon '{$name}' not found in {$prefix}. Skipping.");
                    continue;
                }
                $names[] = $name;
            }
        } else {
            $io->error('Specify icon names or use --all to export the entire set.');

            return Command::FAILURE;
        }

        if ($names === []) {
            $io->warning('No icons to export.');

            return Command::SUCCESS;
        }

        // 4. Resolve output directory
        /** @var string|null $destOption */
        $destOption = $input->getOption('dest');
        $destDir = $destOption ?? './export/' . $prefix;

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0o755, true);
        }

        // 5. Export loop
        $io->title("Exporting {$prefix} Icons");
        $io->text("Source: {$jsonPath}");
        $io->text("Destination: {$destDir}");
        $io->text('Icons to export: ' . \count($names));
        $io->newLine();

        $exported = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $io->createProgressBar(\count($names));
        $progressBar->start();

        foreach ($names as $name) {
            $destFile = $destDir . '/' . $name . '.svg';

            if (file_exists($destFile) && !$overwrite) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            $icon = $provider->get($name);
            if ($icon === null) {
                $failed++;
                $progressBar->advance();
                continue;
            }

            $svg = $icon->toHtml();

            // Add xmlns for standalone SVG files (not needed in HTML but required for standalone use)
            if (!str_contains($svg, 'xmlns=')) {
                $svg = str_replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg"', $svg);
            }

            file_put_contents($destFile, $svg . "\n");
            $exported++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // 6. Summary
        $parts = ["{$exported} exported"];
        if ($skipped > 0) {
            $parts[] = "{$skipped} skipped (existing)";
        }
        if ($failed > 0) {
            $parts[] = "{$failed} failed";
        }

        $io->success(implode(', ', $parts) . " in {$destDir}");

        return Command::SUCCESS;
    }

    /**
     * Resolve the JSON file path from the --json option or auto-detection.
     */
    private function resolveJsonPath(string $prefix, ?string $jsonOption): ?string
    {
        if ($jsonOption !== null) {
            return $jsonOption;
        }

        $manifest = new ManifestManager();
        $jsonDir = $manifest->resolveJsonDirectory();

        return $jsonDir !== null ? $jsonDir . '/' . $prefix . '.json' : null;
    }
}
