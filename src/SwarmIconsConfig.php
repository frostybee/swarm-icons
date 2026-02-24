<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use Frostybee\SwarmIcons\Cache\FileCache;
use Frostybee\SwarmIcons\Cache\NullCache;
use Frostybee\SwarmIcons\Discovery\PackageDiscovery;
use Frostybee\SwarmIcons\Provider\ChainProvider;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use Frostybee\SwarmIcons\Provider\JsonCollectionProvider;
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

    /** @var array<array{prefix: string, jsonFilePath: string}> Deferred JSON collection registrations */
    private array $deferredJsonCollections = [];

    private function __construct()
    {
        $this->manager = new IconManager();
    }

    /**
     * Create a new configuration builder.
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
     */
    public function addIconifySet(string $prefix, int $timeout = 10): self
    {
        $this->deferredIconifySets[] = ['prefix' => $prefix, 'timeout' => $timeout];

        return $this;
    }

    /**
     * Register a JSON collection provider.
     *
     * Loads icons from an Iconify-format JSON collection file.
     *
     * @param string $prefix Provider prefix (e.g., 'tabler', 'mdi')
     * @param string $jsonFilePath Absolute path to the JSON collection file
     */
    public function addJsonCollection(string $prefix, string $jsonFilePath): self
    {
        $this->deferredJsonCollections[] = ['prefix' => $prefix, 'jsonFilePath' => $jsonFilePath];

        return $this;
    }

    /**
     * Register a JSON collection from the iconify/json package.
     *
     * Automatically resolves the JSON file path within the iconify/json package.
     *
     * @param string $prefix Icon set prefix (e.g., 'tabler', 'mdi', 'heroicons')
     * @param string|null $vendorPath Absolute path to vendor directory (auto-detected if null)
     */
    public function addIconifyJsonSet(string $prefix, ?string $vendorPath = null): self
    {
        if ($vendorPath === null) {
            $vendorPath = \dirname(__DIR__, 3) . '/vendor';
        }

        $jsonFilePath = $vendorPath . '/iconify/json/json/' . $prefix . '.json';

        return $this->addJsonCollection($prefix, $jsonFilePath);
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
     */
    public function cache(CacheInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Disable caching (use NullCache).
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
     * Auto-discover and register JSON icon sets from a directory.
     *
     * Scans the given directory for *.json files and registers each as
     * a JSON collection provider, using the filename (without extension)
     * as the prefix.
     *
     * @param string|null $jsonDir Absolute path to directory containing JSON collections.
     *                             Defaults to resources/json/ relative to this package.
     */
    public function discoverJsonSets(?string $jsonDir = null): self
    {
        if ($jsonDir === null) {
            $jsonDir = \dirname(__DIR__) . '/resources/json';
        }
        if (!is_dir($jsonDir)) {
            return $this;
        }
        $files = glob($jsonDir . '/*.json');
        if ($files === false) {
            return $this;
        }
        sort($files);
        foreach ($files as $file) {
            $prefix = pathinfo($file, PATHINFO_FILENAME);
            $this->addJsonCollection($prefix, $file);
        }

        return $this;
    }

    /**
     * Auto-discover and register installed icon set packages.
     *
     * Scans vendor/composer/installed.json for packages that declare
     * "extra.swarm-icons" metadata and registers their providers.
     *
     * @param string|null $vendorPath Absolute path to vendor directory.
     *                                Defaults to the standard Composer vendor location
     *                                relative to this file.
     */
    public function discoverPackages(?string $vendorPath = null): self
    {
        if ($vendorPath === null) {
            // Resolve vendor/ relative to this library's installation path
            $vendorPath = \dirname(__DIR__, 3) . '/vendor';
        }

        PackageDiscovery::registerAll($this->manager, $vendorPath);

        return $this;
    }

    /**
     * Set the fallback icon.
     *
     * @param string $iconName Full icon name with prefix (e.g., 'tabler:question-mark')
     */
    public function fallbackIcon(string $iconName): self
    {
        $this->manager->setFallbackIcon($iconName);

        return $this;
    }

    /**
     * Build and return the configured IconManager.
     */
    public function build(): IconManager
    {
        // Register deferred Iconify sets with the final cache configuration
        $cache = $this->getOrCreateCache();

        foreach ($this->deferredIconifySets as $set) {
            $provider = new IconifyProvider($set['prefix'], $cache, $set['timeout'], $this->cacheTtl);
            $this->manager->register($set['prefix'], $provider);
        }

        foreach ($this->deferredJsonCollections as $set) {
            $provider = new JsonCollectionProvider($set['jsonFilePath'], $cache, $this->cacheTtl);
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
