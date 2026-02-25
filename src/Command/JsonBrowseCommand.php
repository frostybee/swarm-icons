<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\Util\NpmDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Browse all available Iconify icon sets via the Iconify API.
 */
class JsonBrowseCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('json:browse')
            ->setDescription('Browse all available Iconify icon sets')
            ->addOption(
                'search',
                's',
                InputOption::VALUE_REQUIRED,
                'Search/filter icon sets by name, prefix, or category',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>json:browse</info> command lists all available Iconify icon sets.

                    Browse all 200+ icon sets:

                        <info>php bin/swarm-icons json:browse</info>

                    Search by name, prefix, or category:

                        <info>php bin/swarm-icons json:browse --search=emoji</info>
                        <info>php bin/swarm-icons json:browse --search=Material</info>
                        <info>php bin/swarm-icons json:browse -s arrow</info>

                    Download sets you find:

                        <info>php bin/swarm-icons json:download mdi fa-solid heroicons</info>
                    HELP,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $search */
        $search = $input->getOption('search');

        $io->title('Available Iconify Icon Sets');
        $io->text('Fetching collections from Iconify API...');

        $downloader = new NpmDownloader();
        $collections = $downloader->fetchCollections();

        if ($collections === null) {
            $io->error('Could not fetch collections from the Iconify API.');

            return Command::FAILURE;
        }

        $destDir = $this->resolveDestination();

        // Filter by search term if provided
        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $collections = array_filter(
                $collections,
                static fn(array $meta, string $prefix): bool => str_contains(mb_strtolower($prefix), $needle)
                    || str_contains(mb_strtolower($meta['name']), $needle)
                    || str_contains(mb_strtolower($meta['category']), $needle),
                ARRAY_FILTER_USE_BOTH,
            );
        }

        if ($collections === []) {
            $io->warning('No icon sets found matching "' . ($search ?? '') . '".');

            return Command::SUCCESS;
        }

        // Sort by prefix
        ksort($collections);

        $rows = [];
        $totalIcons = 0;
        foreach ($collections as $prefix => $meta) {
            $installed = $destDir !== null && file_exists($destDir . '/' . $prefix . '.json');
            $status = $installed ? '<info>*</info>' : '';
            $rows[] = [
                $prefix,
                $meta['name'],
                number_format($meta['total']),
                $meta['category'],
                $meta['license'],
                $status,
            ];
            $totalIcons += $meta['total'];
        }

        $io->table(
            ['Prefix', 'Name', 'Icons', 'Category', 'License', 'DL'],
            $rows,
        );

        $count = \count($collections);
        $io->text("{$count} icon set(s) found — " . number_format($totalIcons) . ' total icons');
        $io->text('<info>*</info> = already downloaded');
        $io->newLine();
        $io->text('Download with: <info>php bin/swarm-icons json:download PREFIX [PREFIX...]</info>');
        $io->text('Search with:   <info>php bin/swarm-icons json:browse --search=TERM</info>');

        return Command::SUCCESS;
    }

    /**
     * Auto-detect the JSON resources directory.
     */
    private function resolveDestination(): ?string
    {
        $corePath = \dirname(__DIR__, 2) . '/resources/json';
        if (is_dir(\dirname($corePath))) {
            return $corePath;
        }

        $vendorPath = \dirname(__DIR__, 3) . '/frostybee/swarm-icons/resources/json';
        if (is_dir(\dirname($vendorPath))) {
            return $vendorPath;
        }

        return null;
    }
}
