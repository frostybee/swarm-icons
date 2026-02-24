<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use FilesystemIterator;
use Frostybee\SwarmIcons\Cache\FileCache;
use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Testable subclass that overrides httpGet() to avoid real HTTP requests.
 */
class TestableIconifyProvider extends IconifyProvider
{
    private ?string $mockResponse = null;

    /** @var array<string, string|null> */
    private array $hostResponses = [];

    private int $httpCallCount = 0;

    public function setMockResponse(?string $response): void
    {
        $this->mockResponse = $response;
    }

    /**
     * Set responses per host URL prefix for testing fallback hosts.
     *
     * @param array<string, string|null> $responses Map of host => response
     */
    public function setHostResponses(array $responses): void
    {
        $this->hostResponses = $responses;
    }

    public function getHttpCallCount(): int
    {
        return $this->httpCallCount;
    }

    protected function httpGet(string $url): ?string
    {
        $this->httpCallCount++;

        // Check host-specific responses
        foreach ($this->hostResponses as $host => $response) {
            if (str_starts_with($url, $host)) {
                return $response;
            }
        }

        return $this->mockResponse;
    }
}

class IconifyProviderTest extends TestCase
{
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }

    private function createValidResponse(string $name = 'home', string $body = '<path d="M10 10"/>'): string
    {
        return json_encode([
            'prefix' => 'test',
            'icons' => [
                $name => [
                    'body' => $body,
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'width' => 24,
            'height' => 24,
        ]);
    }

    private function createMultiResponse(array $names): string
    {
        $icons = [];
        foreach ($names as $name) {
            $icons[$name] = [
                'body' => "<path d=\"{$name}\"/>",
                'width' => 24,
                'height' => 24,
            ];
        }

        return json_encode([
            'prefix' => 'test',
            'icons' => $icons,
            'width' => 24,
            'height' => 24,
        ]);
    }

    public function test_get_returns_icon_from_api(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse($this->createValidResponse());

        $icon = $provider->get('home');

        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertStringContainsString('path', $icon->getContent());
    }

    public function test_get_returns_null_on_api_failure(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse(null);

        $this->assertNull($provider->get('home'));
    }

    public function test_get_returns_null_for_missing_icon_in_response(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse(json_encode([
            'prefix' => 'heroicons',
            'icons' => [],
        ]));

        $this->assertNull($provider->get('nonexistent'));
    }

    public function test_get_caches_fetched_icon(): void
    {
        $cacheDir = sys_get_temp_dir() . '/swarm-icons-test-iconify-' . uniqid('', true);
        $cache = new FileCache($cacheDir);

        try {
            $provider = new TestableIconifyProvider('heroicons', $cache);
            $provider->setMockResponse($this->createValidResponse());

            // First call fetches from API
            $icon = $provider->get('home');
            $this->assertNotNull($icon);
            $this->assertEquals(1, $provider->getHttpCallCount());

            // Second call should use cache — but since same provider instance,
            // we verify the cache has the item
            $this->assertTrue($cache->has('iconify_' . hash('sha256', 'heroicons.home')));
        } finally {
            $cache->clear();
            $this->deleteDirectory($cacheDir);
        }
    }

    public function test_get_returns_cached_icon(): void
    {
        $cacheDir = sys_get_temp_dir() . '/swarm-icons-test-iconify-' . uniqid('', true);
        $cache = new FileCache($cacheDir);

        try {
            // Pre-populate cache
            $icon = new Icon('<path d="cached"/>', ['viewBox' => '0 0 24 24']);
            $cacheKey = 'iconify_' . hash('sha256', 'heroicons.home');
            $cache->set($cacheKey, $icon);

            $provider = new TestableIconifyProvider('heroicons', $cache);
            $provider->setMockResponse(null); // Should not be called

            $result = $provider->get('home');
            $this->assertNotNull($result);
            $this->assertEquals(0, $provider->getHttpCallCount());
        } finally {
            $cache->clear();
            $this->deleteDirectory($cacheDir);
        }
    }

    public function test_has_returns_true_for_cached_icon(): void
    {
        $cacheDir = sys_get_temp_dir() . '/swarm-icons-test-iconify-' . uniqid('', true);
        $cache = new FileCache($cacheDir);

        try {
            $icon = new Icon('<path/>', ['viewBox' => '0 0 24 24']);
            $cacheKey = 'iconify_' . hash('sha256', 'heroicons.home');
            $cache->set($cacheKey, $icon);

            $provider = new TestableIconifyProvider('heroicons', $cache);

            $this->assertTrue($provider->has('home'));
            $this->assertEquals(0, $provider->getHttpCallCount());
        } finally {
            $cache->clear();
            $this->deleteDirectory($cacheDir);
        }
    }

    public function test_has_returns_false_when_not_found(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse(json_encode([
            'prefix' => 'heroicons',
            'icons' => [],
        ]));

        $this->assertFalse($provider->has('nonexistent'));
    }

    public function test_all_returns_empty_array(): void
    {
        $provider = new TestableIconifyProvider('heroicons');

        $result = $provider->all();
        $this->assertEquals([], iterator_to_array($result));
    }

    public function test_fetch_many(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse($this->createMultiResponse(['home', 'user']));

        $icons = $provider->fetchMany(['home', 'user']);

        $this->assertCount(2, $icons);
        $this->assertArrayHasKey('home', $icons);
        $this->assertArrayHasKey('user', $icons);
        $this->assertInstanceOf(Icon::class, $icons['home']);
        $this->assertInstanceOf(Icon::class, $icons['user']);
    }

    public function test_fetch_many_uses_cache_for_known_icons(): void
    {
        $cacheDir = sys_get_temp_dir() . '/swarm-icons-test-iconify-' . uniqid('', true);
        $cache = new FileCache($cacheDir);

        try {
            // Pre-cache 'home'
            $cachedIcon = new Icon('<path d="cached"/>', ['viewBox' => '0 0 24 24']);
            $cache->set('iconify_' . hash('sha256', 'heroicons.home'), $cachedIcon);

            $provider = new TestableIconifyProvider('heroicons', $cache);
            $provider->setMockResponse($this->createMultiResponse(['user']));

            $icons = $provider->fetchMany(['home', 'user']);

            $this->assertCount(2, $icons);
            $this->assertArrayHasKey('home', $icons);
            $this->assertArrayHasKey('user', $icons);
        } finally {
            $cache->clear();
            $this->deleteDirectory($cacheDir);
        }
    }

    public function test_fetch_many_empty_names(): void
    {
        $provider = new TestableIconifyProvider('heroicons');

        $result = $provider->fetchMany([]);

        $this->assertEquals([], $result);
        $this->assertEquals(0, $provider->getHttpCallCount());
    }

    public function test_get_prefix(): void
    {
        $provider = new TestableIconifyProvider('tabler');

        $this->assertEquals('tabler', $provider->getPrefix());
    }

    public function test_constructor_defaults_to_null_cache(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse($this->createValidResponse());

        // Should work without cache (no errors)
        $icon = $provider->get('home');
        $this->assertNotNull($icon);
    }

    public function test_invalid_json_response_returns_null(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse('not valid json');

        $this->assertNull($provider->get('home'));
    }

    public function test_response_without_icons_key_returns_null(): void
    {
        $provider = new TestableIconifyProvider('heroicons');
        $provider->setMockResponse(json_encode(['prefix' => 'heroicons']));

        $this->assertNull($provider->get('home'));
    }
}
