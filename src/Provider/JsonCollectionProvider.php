<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Provider;

use Frostybee\SwarmIcons\Cache\NullCache;
use Frostybee\SwarmIcons\Exception\ProviderException;
use Frostybee\SwarmIcons\Icon;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * Provides icons from an Iconify JSON collection file.
 *
 * Reads JSON files from packages like `iconify/json` which contain
 * entire icon sets in a single file with the Iconify JSON format.
 *
 * When a cache is provided, individual icons are cached after first
 * resolution so that subsequent requests never need to parse the
 * full JSON file again.
 */
class JsonCollectionProvider implements IconProviderInterface
{
    /** @var array<string, mixed>|null Parsed JSON data, loaded lazily */
    private ?array $data = null;

    private CacheInterface $cache;
    private int $cacheTtl;

    /**
     * @param string $jsonFilePath Path to the JSON collection file
     * @param CacheInterface|null $cache Cache for resolved icons (null = NullCache)
     * @param int $cacheTtl Cache TTL in seconds (0 = infinite)
     */
    public function __construct(
        private readonly string $jsonFilePath,
        ?CacheInterface $cache = null,
        int $cacheTtl = 0,
    ) {
        $this->cache = $cache ?? new NullCache();
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): ?Icon
    {
        $cacheKey = $this->getCacheKey($name);

        // Check cache first — avoids parsing the JSON entirely
        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof Icon) {
            return $cached;
        }

        $this->load();

        $iconData = $this->resolveIcon($name);

        if ($iconData === null) {
            return null;
        }

        // Merge root-level defaults
        if (isset($this->data['width'])) {
            $iconData['width'] ??= $this->data['width'];
        }
        if (isset($this->data['height'])) {
            $iconData['height'] ??= $this->data['height'];
        }

        try {
            $icon = Icon::fromIconifyData($iconData);

            // Cache the resolved icon
            $this->cache->set($cacheKey, $icon, $this->cacheTtl);

            return $icon;
        } catch (Throwable $e) {
            throw new ProviderException(
                "Failed to create icon from JSON collection for '{$name}': {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        // Check cache first
        $cacheKey = $this->getCacheKey($name);
        if ($this->cache->has($cacheKey)) {
            return true;
        }

        $this->load();

        return isset($this->data['icons'][$name])
            || isset($this->data['aliases'][$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        $this->load();

        /** @var array<string, mixed> $icons */
        $icons = $this->data['icons'] ?? [];
        /** @var array<string, mixed> $aliases */
        $aliases = $this->data['aliases'] ?? [];

        return array_merge(array_keys($icons), array_keys($aliases));
    }

    /**
     * Resolve an icon by name, handling aliases.
     *
     * @return array<string, mixed>|null Icon data or null if not found
     */
    private function resolveIcon(string $name): ?array
    {
        // Direct icon lookup
        if (isset($this->data['icons'][$name])) {
            return $this->data['icons'][$name];
        }

        // Alias lookup
        if (isset($this->data['aliases'][$name])) {
            return $this->resolveAlias($name);
        }

        return null;
    }

    /**
     * Resolve an alias to its parent icon data with merged transforms.
     *
     * @return array<string, mixed>|null Resolved icon data
     */
    private function resolveAlias(string $name, int $depth = 0): ?array
    {
        // Prevent infinite loops from circular aliases
        if ($depth > 10) {
            return null;
        }

        $alias = $this->data['aliases'][$name] ?? null;

        if ($alias === null || !isset($alias['parent'])) {
            return null;
        }

        $parentName = $alias['parent'];

        // Parent could be a direct icon or another alias
        if (isset($this->data['icons'][$parentName])) {
            $parentData = $this->data['icons'][$parentName];
        } elseif (isset($this->data['aliases'][$parentName])) {
            $parentData = $this->resolveAlias($parentName, $depth + 1);
            if ($parentData === null) {
                return null;
            }
        } else {
            return null;
        }

        // Start with parent data and apply alias overrides
        $resolved = $parentData;

        // Apply transform overrides from the alias
        foreach (['hFlip', 'vFlip', 'rotate', 'width', 'height'] as $prop) {
            if (isset($alias[$prop])) {
                $resolved[$prop] = $alias[$prop];
            }
        }

        // Override body if alias specifies one
        if (isset($alias['body'])) {
            $resolved['body'] = $alias['body'];
        }

        return $resolved;
    }

    /**
     * Load and parse the JSON file on first access.
     *
     * @throws ProviderException
     */
    private function load(): void
    {
        if ($this->data !== null) {
            return;
        }

        if (!file_exists($this->jsonFilePath)) {
            throw new ProviderException(
                "JSON collection file does not exist: {$this->jsonFilePath}",
            );
        }

        $contents = file_get_contents($this->jsonFilePath);

        if ($contents === false) {
            throw new ProviderException(
                "Failed to read JSON collection file: {$this->jsonFilePath}",
            );
        }

        $data = json_decode($contents, true);

        if (!\is_array($data)) {
            throw new ProviderException(
                "Invalid JSON in collection file: {$this->jsonFilePath}",
            );
        }

        if (!isset($data['icons']) || !\is_array($data['icons'])) {
            throw new ProviderException(
                "JSON collection file missing 'icons' key: {$this->jsonFilePath}",
            );
        }

        $this->data = $data;
    }

    /**
     * Get cache key for an icon.
     */
    private function getCacheKey(string $name): string
    {
        return 'json_collection_' . hash('sha256', $this->jsonFilePath . '.' . $name);
    }
}
