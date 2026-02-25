<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\Cache\NullCache;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\Provider\JsonCollectionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Search for icons by name within an icon set.
 */
class IconSearchCommand extends Command
{
    private const PAGE_SIZE = 30;

    protected function configure(): void
    {
        $this
            ->setName('icon:search')
            ->setDescription('Search for icons by name within an icon set')
            ->addArgument(
                'prefix',
                InputArgument::REQUIRED,
                'Icon set prefix (e.g., tabler, heroicons, mdi)',
            )
            ->addArgument(
                'term',
                InputArgument::REQUIRED,
                'Search term to filter icon names',
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to local SVG directory (searches locally instead of Iconify API)',
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to Iconify JSON collection file',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>icon:search</info> command searches for icons by name.

                    Search an Iconify icon set (fetches from API):

                        <info>php bin/swarm-icons icon:search tabler arrow</info>

                    Search a local SVG directory:

                        <info>php bin/swarm-icons icon:search custom star --path=/path/to/svgs</info>

                    Search a JSON collection file:

                        <info>php bin/swarm-icons icon:search mdi home --json=resources/json/mdi.json</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $prefix */
        $prefix = $input->getArgument('prefix');
        /** @var string $term */
        $term = $input->getArgument('term');
        /** @var string|null $path */
        $path = $input->getOption('path');
        /** @var string|null $jsonPath */
        $jsonPath = $input->getOption('json');

        if ($path !== null) {
            return $this->searchDirectory($io, $prefix, $term, $path);
        }

        if ($jsonPath !== null) {
            return $this->searchJsonCollection($io, $prefix, $term, $jsonPath);
        }

        return $this->searchIconify($io, $prefix, $term);
    }

    private function searchDirectory(SymfonyStyle $io, string $prefix, string $term, string $path): int
    {
        if (!is_dir($path)) {
            $io->error("Directory does not exist: {$path}");
            return Command::FAILURE;
        }

        try {
            $provider = new DirectoryProvider($path);
            $icons = $this->filterIcons(iterator_to_array($provider->all()), $term);

            return $this->displayResults($io, $prefix, $term, $icons, "directory {$path}");
        } catch (Throwable $e) {
            $io->error("Failed to search icons: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function searchJsonCollection(SymfonyStyle $io, string $prefix, string $term, string $jsonPath): int
    {
        if (!file_exists($jsonPath)) {
            $io->error("JSON file does not exist: {$jsonPath}");
            return Command::FAILURE;
        }

        try {
            $provider = new JsonCollectionProvider($jsonPath, new NullCache());
            $icons = $this->filterIcons(iterator_to_array($provider->all()), $term);

            return $this->displayResults($io, $prefix, $term, $icons, "JSON collection {$jsonPath}");
        } catch (Throwable $e) {
            $io->error("Failed to search icons: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function searchIconify(SymfonyStyle $io, string $prefix, string $term): int
    {
        $url = "https://api.iconify.design/collection?prefix=" . urlencode($prefix);

        $ctx = stream_context_create(['http' => [
            'timeout' => 15,
            'header' => "User-Agent: swarm-icons-cli/1.0\r\n",
        ]]);

        $json = @file_get_contents($url, false, $ctx);

        if ($json === false) {
            $io->error("Failed to fetch collection data from Iconify API for prefix '{$prefix}'.");
            return Command::FAILURE;
        }

        $data = json_decode($json, true);

        if (!\is_array($data) || isset($data['error'])) {
            $io->error('Invalid or unknown icon set prefix: ' . $prefix);
            return Command::FAILURE;
        }

        $allIcons = $this->extractIconNames($data);
        $icons = $this->filterIcons($allIcons, $term);
        $title = $data['title'] ?? $prefix;

        return $this->displayResults($io, $prefix, $term, $icons, "{$title} ({$prefix})");
    }

    /**
     * Filter icon names by search term.
     *
     * @param array<int, string> $icons
     *
     * @return array<int, string>
     */
    private function filterIcons(array $icons, string $term): array
    {
        sort($icons);

        return array_values(array_filter(
            $icons,
            fn(string $name): bool => str_contains($name, $term),
        ));
    }

    /**
     * Display search results with prefix:name format and pagination.
     *
     * @param array<int, string> $icons
     */
    private function displayResults(SymfonyStyle $io, string $prefix, string $term, array $icons, string $source): int
    {
        if (empty($icons)) {
            $io->warning("No icons matching '{$term}' found in {$source}");
            return Command::SUCCESS;
        }

        $total = \count($icons);
        $io->newLine();
        $io->text("Searching {$source} icons \"{$term}\"...");
        $io->text("Found <info>{$total}</info> icons.");
        $io->newLine();

        // Prefix each icon name for copy-paste readability
        $prefixed = array_map(
            static fn(string $name): string => $prefix . ':' . $name,
            $icons,
        );

        $this->displayPaginated($io, $prefixed);

        return Command::SUCCESS;
    }

    /**
     * Display icons in a two-column table with pagination.
     *
     * @param array<int, string> $icons Prefixed icon names
     */
    private function displayPaginated(SymfonyStyle $io, array $icons): void
    {
        $total = \count($icons);
        $pages = (int) ceil($total / self::PAGE_SIZE);

        for ($page = 0; $page < $pages; $page++) {
            $chunk = \array_slice($icons, $page * self::PAGE_SIZE, self::PAGE_SIZE);

            // Build two-column rows
            $rows = [];
            $half = (int) ceil(\count($chunk) / 2);
            for ($i = 0; $i < $half; $i++) {
                $left = $chunk[$i] ?? '';
                $right = $chunk[$i + $half] ?? '';
                $rows[] = [$left, $right];
            }

            $io->table([], $rows);

            // Show pagination prompt if more pages remain
            if ($page < $pages - 1) {
                $pageNum = $page + 1;
                $question = new ConfirmationQuestion(
                    "  Page {$pageNum}/{$pages}. Continue? (yes/no) [yes]: ",
                    true,
                );
                if (!$io->askQuestion($question)) {
                    break;
                }
            }
        }
    }

    /**
     * Extract icon names from Iconify collection API response.
     *
     * @param array<string, mixed> $data
     *
     * @return array<int, string>
     */
    private function extractIconNames(array $data): array
    {
        $icons = [];

        if (isset($data['uncategorized']) && \is_array($data['uncategorized'])) {
            $icons = array_merge($icons, $data['uncategorized']);
        }

        if (isset($data['categories']) && \is_array($data['categories'])) {
            foreach ($data['categories'] as $categoryIcons) {
                if (\is_array($categoryIcons)) {
                    $icons = array_merge($icons, $categoryIcons);
                }
            }
        }

        return array_unique(array_filter($icons, 'is_string'));
    }
}
