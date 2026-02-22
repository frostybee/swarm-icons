<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Command;

use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * List available icons from a provider.
 */
class IconListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('icon:list')
            ->setDescription('List available icons from a directory or Iconify set')
            ->addOption(
                'prefix',
                'p',
                InputOption::VALUE_REQUIRED,
                'Icon set prefix (e.g., tabler, heroicons)',
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to SVG directory (for local icon sets)',
            )
            ->addOption(
                'iconify',
                null,
                InputOption::VALUE_NONE,
                'Fetch icon list from Iconify API instead of local directory',
            )
            ->addOption(
                'search',
                's',
                InputOption::VALUE_REQUIRED,
                'Filter icons by substring match',
            )
            ->setHelp(
                <<<'HELP'
                    The <info>icon:list</info> command lists available icons from a provider.

                    List icons from a local directory:

                        <info>php bin/swarm-icons icon:list --path=/path/to/svgs</info>

                    List icons from an Iconify set:

                        <info>php bin/swarm-icons icon:list --prefix=tabler --iconify</info>

                    Filter icons by name:

                        <info>php bin/swarm-icons icon:list --path=/path/to/svgs --search=home</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $prefix */
        $prefix = $input->getOption('prefix');
        /** @var string|null $path */
        $path = $input->getOption('path');
        /** @var bool $useIconify */
        $useIconify = (bool) $input->getOption('iconify');
        /** @var string|null $search */
        $search = $input->getOption('search');

        if ($path !== null) {
            return $this->listFromDirectory($io, $path, $search);
        }

        if ($useIconify && $prefix !== null) {
            return $this->listFromIconify($io, $prefix, $search);
        }

        $io->error('Provide --path for local icons or --prefix with --iconify for Iconify API.');
        return Command::FAILURE;
    }

    private function listFromDirectory(SymfonyStyle $io, string $path, ?string $search): int
    {
        if (!is_dir($path)) {
            $io->error("Directory does not exist: {$path}");
            return Command::FAILURE;
        }

        try {
            $provider = new DirectoryProvider($path);
            $icons = iterator_to_array($provider->all());
            sort($icons);

            if ($search !== null && $search !== '') {
                $icons = array_values(array_filter(
                    $icons,
                    fn(string $name): bool => str_contains($name, $search),
                ));
            }

            if (empty($icons)) {
                $io->warning($search !== null
                    ? "No icons matching '{$search}' found in: {$path}"
                    : "No icons found in: {$path}");
                return Command::SUCCESS;
            }

            $io->title('Icons in ' . $path);
            $this->displayIcons($io, $icons);
            $io->newLine();
            $io->text("<info>" . \count($icons) . "</info> icon(s) found");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error("Failed to list icons: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function listFromIconify(SymfonyStyle $io, string $prefix, ?string $search): int
    {
        $io->text("Fetching icon list for '{$prefix}' from Iconify API...");

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
        if (!\is_array($data)) {
            $io->error('Invalid response from Iconify API.');
            return Command::FAILURE;
        }

        // Extract icon names from the collection data
        $icons = $this->extractIconNames($data);
        sort($icons);

        if ($search !== null && $search !== '') {
            $icons = array_values(array_filter(
                $icons,
                fn(string $name): bool => str_contains($name, $search),
            ));
        }

        if (empty($icons)) {
            $io->warning($search !== null
                ? "No icons matching '{$search}' in Iconify set '{$prefix}'"
                : "No icons found in Iconify set '{$prefix}'");
            return Command::SUCCESS;
        }

        $title = $data['title'] ?? $prefix;
        $io->title("{$title} ({$prefix})");
        $this->displayIcons($io, $icons);
        $io->newLine();
        $io->text("<info>" . \count($icons) . "</info> icon(s) found");

        return Command::SUCCESS;
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
        // The API returns icons grouped by categories, or as a flat "uncategorized" list
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

    /**
     * Display icons in a formatted multi-column layout.
     *
     * @param array<int, string> $icons
     */
    private function displayIcons(SymfonyStyle $io, array $icons): void
    {
        // Determine column width based on longest icon name
        $maxLen = max(array_map('strlen', $icons));
        $colWidth = $maxLen + 4;
        $termWidth = 80;
        $cols = max(1, (int) floor($termWidth / $colWidth));

        $rows = array_chunk($icons, $cols);
        foreach ($rows as $row) {
            $line = '';
            foreach ($row as $icon) {
                $line .= str_pad($icon, $colWidth);
            }
            $io->text(rtrim($line));
        }
    }
}
