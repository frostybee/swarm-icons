<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Manifest\ManifestGenerator;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Generate a JSON manifest of available icon names.
 */
class ManifestGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('manifest:generate')
            ->setDescription('Generate a JSON manifest of available icon names')
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to SVG directory',
            )
            ->addOption(
                'prefix',
                'p',
                InputOption::VALUE_REQUIRED,
                'Icon set prefix',
                'icons',
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file path',
                'icons.json',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>manifest:generate</info> command generates a JSON manifest of icon names.

                    Generate from a local directory:

                        <info>php bin/swarm-icons manifest:generate --path=/path/to/svgs --prefix=custom</info>

                    Specify output file:

                        <info>php bin/swarm-icons manifest:generate --path=/path/to/svgs --prefix=custom --output=icons.json</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $path */
        $path = $input->getOption('path');
        /** @var string $prefix */
        $prefix = $input->getOption('prefix');
        /** @var string $outputPath */
        $outputPath = $input->getOption('output');

        if ($path === null || $path === '') {
            $io->error('The --path option is required.');
            return Command::FAILURE;
        }

        if (!is_dir($path)) {
            $io->error("Directory does not exist: {$path}");
            return Command::FAILURE;
        }

        try {
            $manager = new IconManager();
            $manager->register($prefix, new DirectoryProvider($path));

            $generator = new ManifestGenerator();
            $stats = $generator->toFile($manager, $outputPath);

            $io->success([
                "Manifest generated with {$stats['icons']} icon(s) across {$stats['prefixes']} prefix(es)",
                "Output: {$outputPath}",
            ]);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error("Failed to generate manifest: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
