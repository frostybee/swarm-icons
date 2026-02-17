<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Provider;

use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\Exception\ProviderException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

/**
 * Provides icons from a local filesystem directory.
 *
 * Maps icon names to .svg files in a directory.
 * Supports subdirectory notation (e.g., "outline/home" → outline/home.svg).
 */
class DirectoryProvider implements IconProviderInterface
{
    /** @var array<string, Icon>|null Cached icon instances */
    private ?array $cache = null;

    /**
     * @param string $directory Path to the directory containing SVG files
     * @param bool $recursive Whether to scan subdirectories recursively
     * @param string $extension File extension to look for (default: 'svg')
     */
    public function __construct(
        private readonly string $directory,
        private readonly bool $recursive = true,
        private readonly string $extension = 'svg'
    ) {
        if (!is_dir($directory)) {
            throw new ProviderException("Directory does not exist: {$directory}");
        }

        if (!is_readable($directory)) {
            throw new ProviderException("Directory is not readable: {$directory}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): ?Icon
    {
        if ($this->cache !== null && array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $filePath = $this->resolveFilePath($name);

        if ($filePath === null) {
            return null;
        }

        try {
            return Icon::fromFile($filePath);
        } catch (\Throwable $e) {
            throw new ProviderException(
                "Failed to load icon '{$name}' from {$filePath}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return $this->resolveFilePath($name) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        if ($this->cache !== null) {
            return array_keys($this->cache);
        }

        $icons = [];

        if ($this->recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->directory,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $iterator = new FilesystemIterator(
                $this->directory,
                FilesystemIterator::SKIP_DOTS
            );
        }

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== $this->extension) {
                continue;
            }

            // Calculate relative path from base directory
            $relativePath = str_replace($this->directory . DIRECTORY_SEPARATOR, '', $file->getPathname());

            // Convert to icon name (remove extension, normalize separators)
            $iconName = $this->pathToIconName($relativePath);

            $icons[] = $iconName;
        }

        return $icons;
    }

    /**
     * Resolve icon name to file path.
     *
     * @param string $name Icon name (e.g., "home" or "outline/home")
     * @return string|null Absolute file path or null if not found
     */
    private function resolveFilePath(string $name): ?string
    {
        // Normalize icon name to file path
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $name);
        $filePath = $this->directory . DIRECTORY_SEPARATOR . $relativePath . '.' . $this->extension;

        // Security check: ensure resolved path is within the directory
        $realPath = realpath($filePath);
        $realDirectory = realpath($this->directory);

        if ($realPath === false || $realDirectory === false) {
            return null;
        }

        if (!str_starts_with($realPath, $realDirectory . DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (!file_exists($realPath) || !is_file($realPath)) {
            return null;
        }

        return $realPath;
    }

    /**
     * Convert file path to icon name.
     *
     * @param string $path Relative file path
     * @return string Icon name
     */
    private function pathToIconName(string $path): string
    {
        // Remove extension
        $withoutExtension = preg_replace('/\.' . preg_quote($this->extension, '/') . '$/', '', $path);

        if ($withoutExtension === null) {
            return $path;
        }

        // Normalize directory separators to forward slashes
        return str_replace(DIRECTORY_SEPARATOR, '/', $withoutExtension);
    }

    /**
     * Preload all icons into memory cache.
     *
     * Useful for performance when all icons will be used.
     *
     * @return void
     */
    public function preload(): void
    {
        $this->cache = [];

        foreach ($this->all() as $name) {
            $icon = $this->get($name);
            if ($icon !== null) {
                $this->cache[$name] = $icon;
            }
        }
    }

    /**
     * Clear the memory cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }
}
