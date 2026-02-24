<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\Cache\FileCache;
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Pre-fetch icons from the Iconify API and cache them locally.
 */
class CacheWarmCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cache:warm')
            ->setDescription('Pre-fetch icons from Iconify API and cache them locally')
            ->addOption(
                'prefix',
                'p',
                InputOption::VALUE_REQUIRED,
                'Icon set prefix (e.g., tabler, heroicons, lucide)',
            )
            ->addOption(
                'icons',
                'i',
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of icon names to warm (e.g., home,user,settings)',
            )
            ->addOption(
                'cache-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Cache directory path',
                sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'swarm-icons',
            )
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_REQUIRED,
                'HTTP timeout in seconds',
                '10',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>cache:warm</info> command pre-fetches icons from the Iconify API and caches them locally.

                    Warm specific icons:

                        <info>php bin/swarm-icons cache:warm --prefix=tabler --icons=home,user,settings</info>

                    Use a custom cache directory:

                        <info>php bin/swarm-icons cache:warm --prefix=tabler --icons=home --cache-path=/var/cache/icons</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $prefix */
        $prefix = $input->getOption('prefix');
        /** @var string|null $iconsList */
        $iconsList = $input->getOption('icons');
        /** @var string $cachePath */
        $cachePath = $input->getOption('cache-path');
        /** @var string $timeoutStr */
        $timeoutStr = $input->getOption('timeout');

        if (!is_numeric($timeoutStr) || (int) $timeoutStr <= 0) {
            $io->error("Invalid --timeout value: '{$timeoutStr}'. Must be a positive integer.");
            return Command::FAILURE;
        }

        $timeout = (int) $timeoutStr;

        if ($prefix === null || $prefix === '') {
            $io->error('The --prefix option is required (e.g., --prefix=tabler).');
            return Command::FAILURE;
        }

        if ($iconsList === null || $iconsList === '') {
            $io->error('The --icons option is required (e.g., --icons=home,user,settings).');
            return Command::FAILURE;
        }

        $names = array_filter(array_map('trim', explode(',', $iconsList)));

        if (empty($names)) {
            $io->warning('No icon names provided.');
            return Command::SUCCESS;
        }

        $io->title("Warming cache for '{$prefix}' icons");
        $io->text("Icons to fetch: " . \count($names));
        $io->text("Cache path: {$cachePath}");
        $io->newLine();

        try {
            $cache = new FileCache($cachePath);
            $provider = new IconifyProvider($prefix, $cache, $timeout);

            $io->text('Fetching icons from Iconify API...');

            $fetched = $provider->fetchMany($names);

            $fetchedCount = \count($fetched);
            $failedCount = \count($names) - $fetchedCount;

            $stats = $cache->getStats();

            $io->newLine();

            if ($failedCount > 0) {
                $fetchedNames = array_keys($fetched);
                $failed = array_diff($names, $fetchedNames);
                $io->warning("Could not fetch: " . implode(', ', $failed));
            }

            $io->success([
                "Warmed {$fetchedCount} icon(s) for prefix '{$prefix}'",
                $failedCount > 0 ? "Failed: {$failedCount}" : 'All icons fetched successfully',
                "Cache now contains {$stats['files']} files (" . $this->formatBytes($stats['size']) . ')',
            ]);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error("Failed to warm cache: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

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
