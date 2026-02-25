<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Provider;

use Frostybee\SwarmIcons\Cache\NullCache;
use Frostybee\SwarmIcons\Exception\ProviderException;
use Frostybee\SwarmIcons\Icon;
use JsonException;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * Provides icons from the Iconify API.
 *
 * Fetches icons from api.iconify.design with automatic caching.
 * Supports 200,000+ icons from popular icon sets.
 */
class IconifyProvider implements IconProviderInterface
{
    private const API_BASE_URL = 'https://api.iconify.design';
    private const FALLBACK_HOSTS = [
        'https://api.iconify.design',
        'https://api.simplesvg.com',
        'https://api.unisvg.com',
    ];

    private CacheInterface $cache;
    private int $timeout;
    private int $cacheTtl;

    /** @var array<string> */
    private array $apiHosts;

    /**
     * @param string $prefix Icon set prefix (e.g., 'heroicons', 'lucide', 'tabler')
     * @param CacheInterface|null $cache Cache implementation (null = NullCache)
     * @param int $timeout HTTP request timeout in seconds
     * @param int $cacheTtl Cache TTL in seconds (0 = infinite)
     * @param array<string> $apiHosts Custom API hosts (default: Iconify official hosts)
     */
    public function __construct(
        private readonly string $prefix,
        ?CacheInterface $cache = null,
        int $timeout = 10,
        int $cacheTtl = 0,
        array $apiHosts = [],
    ) {
        $this->cache = $cache ?? new NullCache();
        $this->timeout = $timeout;
        $this->cacheTtl = $cacheTtl;
        $this->apiHosts = !empty($apiHosts) ? $apiHosts : self::FALLBACK_HOSTS;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): ?Icon
    {
        $cacheKey = $this->getCacheKey($name);

        // Check cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof Icon) {
            return $cached;
        }

        // Fetch from API
        $iconData = $this->fetchIcon($name);

        if ($iconData === null) {
            return null;
        }

        try {
            $icon = Icon::fromIconifyData($iconData);

            // Cache the icon
            $this->cache->set($cacheKey, $icon, $this->cacheTtl);

            return $icon;
        } catch (Throwable $e) {
            throw new ProviderException(
                "Failed to create icon from Iconify data for '{$this->prefix}:{$name}': {$e->getMessage()}",
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
        // Check cache first to avoid unnecessary HTTP requests
        $cacheKey = $this->getCacheKey($name);
        if ($this->cache->has($cacheKey)) {
            return true;
        }

        return $this->get($name) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        // Iconify API doesn't provide a list endpoint
        // This would require fetching the collection metadata
        return [];
    }

    /**
     * Fetch multiple icons at once (batch operation).
     *
     * More efficient than multiple individual get() calls.
     *
     * @param array<string> $names Icon names
     *
     * @return array<string, Icon> Map of name => Icon
     */
    public function fetchMany(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        $icons = [];
        $toFetch = [];

        // Check cache first
        foreach ($names as $name) {
            $cacheKey = $this->getCacheKey($name);
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof Icon) {
                $icons[$name] = $cached;
            } else {
                $toFetch[] = $name;
            }
        }

        // Fetch uncached icons from API
        if (!empty($toFetch)) {
            $fetched = $this->fetchMultipleIcons($toFetch);

            foreach ($fetched as $name => $iconData) {
                try {
                    $icon = Icon::fromIconifyData($iconData);
                    $icons[$name] = $icon;

                    // Cache it
                    $cacheKey = $this->getCacheKey($name);
                    $this->cache->set($cacheKey, $icon, $this->cacheTtl);
                } catch (Throwable) {
                    // Skip invalid icons
                    continue;
                }
            }
        }

        return $icons;
    }

    /**
     * Fetch a single icon from the Iconify API.
     *
     * @param string $name Icon name
     *
     * @return array<string, mixed>|null Icon data or null if not found
     */
    private function fetchIcon(string $name): ?array
    {
        $url = self::API_BASE_URL . '/' . urlencode($this->prefix) . '.json?icons=' . urlencode($name);

        $response = $this->makeHttpRequest($url);

        if ($response === null) {
            return null;
        }

        $data = $this->parseJsonResponse($response);

        if ($data === null || !isset($data['icons'][$name])) {
            return null;
        }

        $iconData = $data['icons'][$name];

        // Merge with set defaults if present
        if (isset($data['width'])) {
            $iconData['width'] ??= $data['width'];
        }
        if (isset($data['height'])) {
            $iconData['height'] ??= $data['height'];
        }

        return $iconData;
    }

    /**
     * Fetch multiple icons from the Iconify API.
     *
     * @param array<string> $names Icon names
     *
     * @return array<string, array<string, mixed>> Map of name => icon data
     */
    private function fetchMultipleIcons(array $names): array
    {
        $iconsList = implode(',', $names);
        $url = self::API_BASE_URL . '/' . urlencode($this->prefix) . '.json?icons=' . urlencode($iconsList);

        $response = $this->makeHttpRequest($url);

        if ($response === null) {
            return [];
        }

        $data = $this->parseJsonResponse($response);

        if ($data === null || !isset($data['icons'])) {
            return [];
        }

        $result = [];

        foreach ($data['icons'] as $name => $iconData) {
            // Merge with set defaults if present
            if (isset($data['width'])) {
                $iconData['width'] ??= $data['width'];
            }
            if (isset($data['height'])) {
                $iconData['height'] ??= $data['height'];
            }

            $result[$name] = $iconData;
        }

        return $result;
    }

    /**
     * Make HTTP request with fallback hosts.
     *
     * @return string|null Response body or null on failure
     */
    private function makeHttpRequest(string $url): ?string
    {
        foreach ($this->apiHosts as $host) {
            // Replace base URL with current host
            $requestUrl = str_replace(self::API_BASE_URL, $host, $url);

            try {
                $response = $this->httpGet($requestUrl);

                if ($response !== null) {
                    return $response;
                }
            } catch (Throwable) {
                // Try next host
                continue;
            }
        }

        return null;
    }

    /**
     * Perform HTTP GET request using file_get_contents.
     *
     * @return string|null Response body or null on failure
     */
    protected function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => "User-Agent: SwarmIcons/1.0\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        // Check HTTP status code
        $responseHeaders = $http_response_header ?? [];
        if (!isset($responseHeaders[0]) || !preg_match('/^HTTP\/[\d.]+ 200\b/', $responseHeaders[0])) {
            return null;
        }

        return $response;
    }

    /**
     * Parse JSON response.
     *
     * @return array<string, mixed>|null
     */
    private function parseJsonResponse(string $response): ?array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!\is_array($data)) {
                return null;
            }

            return $data;
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * Get cache key for an icon.
     */
    private function getCacheKey(string $name): string
    {
        return 'iconify_' . hash('sha256', $this->prefix . '.' . $name);
    }

    /**
     * Get the icon set prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
