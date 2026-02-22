<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\Cache\FileCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Clear the icon cache.
 */
class CacheClearCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear the icon cache')
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Cache directory path',
                sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'swarm-icons',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>cache:clear</info> command clears all cached icons.

                        <info>php bin/swarm-icons cache:clear</info>

                    Use the --path option to specify a custom cache directory:

                        <info>php bin/swarm-icons cache:clear --path=/var/cache/icons</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $cachePath */
        $cachePath = $input->getOption('path');

        if (!is_dir($cachePath)) {
            $io->warning("Cache directory does not exist: {$cachePath}");
            return Command::SUCCESS;
        }

        try {
            $cache = new FileCache($cachePath);

            // Get stats before clearing
            $stats = $cache->getStats();

            $cache->clear();

            $io->success([
                'Cache cleared successfully!',
                "Removed {$stats['files']} cached files (" . $this->formatBytes($stats['size']) . ')',
                "Location: {$cachePath}",
            ]);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error("Failed to clear cache: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Format bytes to human-readable size.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return round($bytes / 1048576, 2) . ' MB';
    }
}
