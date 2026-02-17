<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use Frostybee\SwarmIcons\Provider\ChainProvider;
use Frostybee\SwarmIcons\Cache\FileCache;
use Frostybee\SwarmIcons\Cache\NullCache;
use Psr\SimpleCache\CacheInterface;

/**
 * Fluent configuration builder for IconManager.
 *
 * Provides a convenient API for setting up icon providers and defaults.
 */
class SwarmIconsConfig
{
    private IconManager $manager;
    private ?CacheInterface $cache = null;
    private ?string $cachePath = null;
    private int $cacheTtl = 0;

    /** @var array<string, string> */
    private array $defaultAttributes = [];

    /** @var array<string, array<string, string>> */
    private array $prefixAttributes = [];

    /** @var array<array{prefix: string, timeout: int}> Deferred Iconify registrations */
    private array $deferredIconifySets = [];

    /** @var array<array{prefix: string, directory: string, recursive: bool, timeout: int}> Deferred hybrid registrations */
    private array $deferredHybridSets = [];

    private function __construct()
    {
        $this->manager = new IconManager();
    }

    /**
     * Create a new configuration builder.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Register a directory provider.
     *
     * @param string $prefix Provider prefix (e.g., 'custom', 'my-icons')
     * @param string $directory Path to SVG directory
     * @param bool $recursive Whether to scan subdirectories
     * @return self
     */
    public function addDirectory(string $prefix, string $directory, bool $recursive = true): self
    {
        $provider = new DirectoryProvider($directory, $recursive);
        $this->manager->register($prefix, $provider);

        return $this;
    }

    /**
     * Register an Iconify API provider.
     *
     * @param string $prefix Icon set prefix (e.g., 'heroicons', 'lucide', 'tabler')
     * @param int $timeout HTTP timeout in seconds
     * @return self
     */
    public function addIconifySet(string $prefix, int $timeout = 10): self
    {
        $this->deferredIconifySets[] = ['prefix' => $prefix, 'timeout' => $timeout];

        return $this;
    }

    /**
     * Register a hybrid provider (local files with Iconify fallback).
     *
     * Tries local directory first, falls back to Iconify API if not found.
     *
     * @param string $prefix Provider prefix
     * @param string $directory Path to local SVG directory
     * @param bool $recursive Whether to scan subdirectories
     * @param int $timeout HTTP timeout for Iconify API
     * @return self
     */
    public function addHybridSet(string $prefix, string $directory, bool $recursive = true, int $timeout = 10): self
    {
        $this->deferredHybridSets[] = [
            'prefix' => $prefix,
            'directory' => $directory,
            'recursive' => $recursive,
            'timeout' => $timeout,
        ];

        return $this;
    }

    /**
     * Set the default icon prefix.
     *
     * @param string $prefix
     * @return self
     */
    public function defaultPrefix(string $prefix): self
    {
        $this->manager->setDefaultPrefix($prefix);

        return $this;
    }

    /**
     * Set the cache directory path.
     *
     * @param string $path Absolute path to cache directory
     * @param int $ttl Cache TTL in seconds (0 = infinite)
     * @return self
     */
    public function cachePath(string $path, int $ttl = 0): self
    {
        $this->cachePath = $path;
        $this->cacheTtl = $ttl;
        $this->cache = null; // Reset cache to force recreation

        return $this;
    }

    /**
     * Set a custom cache implementation.
     *
     * @param CacheInterface $cache
     * @return self
     */
    public function cache(CacheInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Disable caching (use NullCache).
     *
     * @return self
     */
    public function noCache(): self
    {
        $this->cache = new NullCache();

        return $this;
    }

    /**
     * Set global default attributes for all icons.
     *
     * @param array<string, string> $attributes
     * @return self
     */
    public function defaultAttributes(array $attributes): self
    {
        $this->defaultAttributes = array_merge($this->defaultAttributes, $attributes);

        return $this;
    }

    /**
     * Set prefix-specific default attributes.
     *
     * @param string $prefix Provider prefix
     * @param array<string, string> $attributes
     * @return self
     */
    public function prefixAttributes(string $prefix, array $attributes): self
    {
        if (!isset($this->prefixAttributes[$prefix])) {
            $this->prefixAttributes[$prefix] = [];
        }

        $this->prefixAttributes[$prefix] = array_merge($this->prefixAttributes[$prefix], $attributes);

        return $this;
    }

    /**
     * Set the fallback icon.
     *
     * @param string $iconName Full icon name with prefix (e.g., 'tabler:question-mark')
     * @return self
     */
    public function fallbackIcon(string $iconName): self
    {
        $this->manager->setFallbackIcon($iconName);

        return $this;
    }

    /**
     * Build and return the configured IconManager.
     *
     * @return IconManager
     */
    public function build(): IconManager
    {
        // Register deferred Iconify sets with the final cache configuration
        $cache = $this->getOrCreateCache();

        foreach ($this->deferredIconifySets as $set) {
            $provider = new IconifyProvider($set['prefix'], $cache, $set['timeout'], $this->cacheTtl);
            $this->manager->register($set['prefix'], $provider);
        }

        foreach ($this->deferredHybridSets as $set) {
            $localProvider = new DirectoryProvider($set['directory'], $set['recursive']);
            $iconifyProvider = new IconifyProvider($set['prefix'], $cache, $set['timeout'], $this->cacheTtl);
            $chainProvider = new ChainProvider([$localProvider, $iconifyProvider]);
            $this->manager->register($set['prefix'], $chainProvider);
        }

        // Apply default attributes
        $renderer = new IconRenderer($this->defaultAttributes, $this->prefixAttributes);
        $this->manager->setRenderer($renderer);

        return $this->manager;
    }

    /**
     * Get or create the cache instance.
     *
     * @return CacheInterface
     */
    private function getOrCreateCache(): CacheInterface
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if ($this->cachePath !== null) {
            $this->cache = new FileCache($this->cachePath, $this->cacheTtl);
            return $this->cache;
        }

        // Default: use temp directory
        $defaultPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'swarm-icons';
        $this->cache = new FileCache($defaultPath, $this->cacheTtl);

        return $this->cache;
    }
}
