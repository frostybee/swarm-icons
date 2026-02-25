<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generate a starter SwarmIcons configuration file.
 */
class InitCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Generate a starter SwarmIcons configuration file')
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file path',
                'swarm-icons.php',
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing file',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>init</info> command generates a starter configuration file.

                        <info>php bin/swarm-icons init</info>

                    Specify a custom output path:

                        <info>php bin/swarm-icons init --output=config/swarm-icons.php</info>

                    Overwrite an existing file:

                        <info>php bin/swarm-icons init --force</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $outputPath */
        $outputPath = $input->getOption('output');
        /** @var bool $force */
        $force = (bool) $input->getOption('force');

        // Resolve relative paths against current working directory
        if (!str_starts_with($outputPath, '/') && !preg_match('#^[A-Za-z]:[/\\\\]#', $outputPath)) {
            $cwd = getcwd();
            if ($cwd === false) {
                $io->error('Cannot determine current working directory.');
                return Command::FAILURE;
            }
            $outputPath = $cwd . DIRECTORY_SEPARATOR . $outputPath;
        }

        if (file_exists($outputPath) && !$force) {
            $io->error([
                "File already exists: {$outputPath}",
                'Use --force to overwrite.',
            ]);
            return Command::FAILURE;
        }

        // Ensure parent directory exists
        $dir = \dirname($outputPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0o755, true)) {
                $io->error("Cannot create directory: {$dir}");
                return Command::FAILURE;
            }
        }

        $content = $this->getTemplate();

        if (file_put_contents($outputPath, $content) === false) {
            $io->error("Failed to write file: {$outputPath}");
            return Command::FAILURE;
        }

        $io->success([
            'Configuration file created!',
            "Location: {$outputPath}",
            'Edit the file to configure your icon providers, cache, and defaults.',
        ]);

        return Command::SUCCESS;
    }

    private function getTemplate(): string
    {
        return <<<'PHP'
            <?php

            /**
             * SwarmIcons Configuration
             *
             * This file configures the SwarmIcons icon manager. Uncomment and adjust
             * the options below to suit your project.
             *
             * @see https://github.com/frostybee/swarm-icons
             */

            declare(strict_types=1);

            use Frostybee\SwarmIcons\SwarmIcons;
            use Frostybee\SwarmIcons\SwarmIconsConfig;

            $manager = SwarmIconsConfig::create()

                // -------------------------------------------------------------------------
                // Icon Providers
                // -------------------------------------------------------------------------

                // Local SVG directory:
                // ->addDirectory('custom', __DIR__ . '/resources/icons')

                // Iconify API (fetches on demand, caches locally):
                // ->addIconifySet('tabler')

                // Hybrid: local files first, Iconify API as fallback:
                // ->addHybridSet('heroicons', __DIR__ . '/resources/heroicons')

                // Auto-discover installed swarm-icons-* Composer packages:
                // ->discoverPackages()

                // -------------------------------------------------------------------------
                // Cache
                // -------------------------------------------------------------------------

                // File-based cache (recommended for production):
                ->cachePath(__DIR__ . '/cache/icons')

                // Cache TTL in seconds (0 = forever):
                // ->cachePath(__DIR__ . '/cache/icons', ttl: 3600)

                // Disable caching (for development):
                // ->noCache()

                // -------------------------------------------------------------------------
                // Defaults
                // -------------------------------------------------------------------------

                // Default prefix (allows icon('home') instead of icon('tabler:home')):
                // ->defaultPrefix('tabler')

                // Global attributes applied to all icons:
                // ->defaultAttributes(['class' => 'icon'])

                // Prefix-specific attributes:
                // ->prefixAttributes('tabler', ['stroke-width' => '1.5'])

                // Fallback icon when requested icon is not found:
                // ->fallbackIcon('tabler:question-mark')

                ->build();

            // Register globally for the swarm_icon() helper function:
            SwarmIcons::setManager($manager);

            return $manager;

            PHP;
    }
}
