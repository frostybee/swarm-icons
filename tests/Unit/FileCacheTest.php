<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use DateInterval;
use FilesystemIterator;
use Frostybee\SwarmIcons\Cache\FileCache;
use Frostybee\SwarmIcons\Exception\CacheInvalidArgumentException;
use Frostybee\SwarmIcons\Icon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/swarm-icons-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->cacheDir);
    }

    /**
     * Write a cache entry with a pre-expired timestamp to avoid sleep() in tests.
     */
    private function writeExpiredCacheEntry(string $key, mixed $value): void
    {
        $hash = hash('sha256', $key);
        $prefix = substr($hash, 0, 2);
        $filePath = $this->cacheDir . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . $hash . '.cache';

        $dir = \dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $data = [
            'value' => $value,
            'expires_at' => time() - 10, // Already expired
            'created_at' => time() - 20,
        ];

        file_put_contents($filePath, serialize($data));
    }

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
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }

    public function test_constructor_creates_directory(): void
    {
        $this->assertDirectoryDoesNotExist($this->cacheDir);

        new FileCache($this->cacheDir);

        $this->assertDirectoryExists($this->cacheDir);
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $cache = new FileCache($this->cacheDir);

        $this->assertNull($cache->get('nonexistent'));
        $this->assertEquals('fallback', $cache->get('nonexistent', 'fallback'));
    }

    public function test_set_and_get(): void
    {
        $cache = new FileCache($this->cacheDir);

        $this->assertTrue($cache->set('key1', 'value1'));
        $this->assertEquals('value1', $cache->get('key1'));
    }

    public function test_set_and_get_with_complex_values(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('array', ['foo' => 'bar', 'nested' => [1, 2, 3]]);
        $this->assertEquals(['foo' => 'bar', 'nested' => [1, 2, 3]], $cache->get('array'));

        $cache->set('int', 42);
        $this->assertEquals(42, $cache->get('int'));

        $cache->set('bool', true);
        $this->assertTrue($cache->get('bool'));
    }

    public function test_set_and_get_icon_object(): void
    {
        $cache = new FileCache($this->cacheDir);

        $icon = new Icon('<path d="M10 10"/>', ['width' => '24', 'height' => '24']);
        $cache->set('icon', $icon);

        $cached = $cache->get('icon');
        $this->assertInstanceOf(Icon::class, $cached);
        $this->assertEquals($icon->getContent(), $cached->getContent());
        $this->assertEquals($icon->getAttributes(), $cached->getAttributes());
    }

    public function test_get_returns_default_for_expired_item(): void
    {
        $cache = new FileCache($this->cacheDir);

        // Write a cache entry that is already expired
        $this->writeExpiredCacheEntry('expiring', 'value');

        $this->assertNull($cache->get('expiring'));
    }

    public function test_set_with_zero_ttl_never_expires(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('permanent', 'value', 0);
        $this->assertEquals('value', $cache->get('permanent'));
    }

    public function test_set_with_negative_ttl_deletes(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key', 'value');
        $this->assertTrue($cache->has('key'));

        $result = $cache->set('key', 'value', -1);
        $this->assertTrue($result);
        $this->assertFalse($cache->has('key'));
    }

    public function test_set_with_date_interval_ttl(): void
    {
        $cache = new FileCache($this->cacheDir);

        $ttl = new DateInterval('PT1H'); // 1 hour
        $cache->set('interval', 'value', $ttl);

        $this->assertEquals('value', $cache->get('interval'));
    }

    public function test_set_with_null_ttl_uses_default(): void
    {
        // Default TTL of 0 means infinite
        $cache = new FileCache($this->cacheDir, defaultTtl: 0);

        $cache->set('key', 'value', null);
        $this->assertEquals('value', $cache->get('key'));
    }

    public function test_delete_existing_key(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key', 'value');
        $this->assertTrue($cache->has('key'));

        $this->assertTrue($cache->delete('key'));
        $this->assertFalse($cache->has('key'));
    }

    public function test_delete_nonexistent_key(): void
    {
        $cache = new FileCache($this->cacheDir);

        $this->assertTrue($cache->delete('nonexistent'));
    }

    public function test_clear(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        $this->assertTrue($cache->clear());

        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
        $this->assertNull($cache->get('key3'));
    }

    public function test_has_returns_true_for_existing(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key', 'value');
        $this->assertTrue($cache->has('key'));
    }

    public function test_has_returns_false_for_missing(): void
    {
        $cache = new FileCache($this->cacheDir);

        $this->assertFalse($cache->has('nonexistent'));
    }

    public function test_has_returns_false_for_expired(): void
    {
        $cache = new FileCache($this->cacheDir);

        // Write a cache entry that is already expired
        $this->writeExpiredCacheEntry('expiring', 'value');

        $this->assertFalse($cache->has('expiring'));
    }

    public function test_get_multiple(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');

        $result = $cache->getMultiple(['key1', 'key2', 'key3'], 'default');

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'default',
        ], $result);
    }

    public function test_set_multiple(): void
    {
        $cache = new FileCache($this->cacheDir);

        $result = $cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $this->assertTrue($result);
        $this->assertEquals('value1', $cache->get('key1'));
        $this->assertEquals('value2', $cache->get('key2'));
    }

    public function test_delete_multiple(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');

        $result = $cache->deleteMultiple(['key1', 'key2']);

        $this->assertTrue($result);
        $this->assertFalse($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
    }

    public function test_validate_key_rejects_empty(): void
    {
        $cache = new FileCache($this->cacheDir);

        $this->expectException(CacheInvalidArgumentException::class);
        $cache->get('');
    }

    #[DataProvider('reservedCharacterProvider')]
    public function test_validate_key_rejects_reserved_characters(string $key): void
    {
        $cache = new FileCache($this->cacheDir);

        $this->expectException(CacheInvalidArgumentException::class);
        $cache->get($key);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function reservedCharacterProvider(): array
    {
        return [
            'curly open' => ['key{bad'],
            'curly close' => ['key}bad'],
            'paren open' => ['key(bad'],
            'paren close' => ['key)bad'],
            'forward slash' => ['key/bad'],
            'backslash' => ['key\\bad'],
            'at sign' => ['key@bad'],
            'colon' => ['key:bad'],
        ];
    }

    public function test_get_stats_empty_cache(): void
    {
        $cache = new FileCache($this->cacheDir);

        $stats = $cache->getStats();

        $this->assertEquals(['files' => 0, 'size' => 0], $stats);
    }

    public function test_get_stats_with_items(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');

        $stats = $cache->getStats();

        $this->assertEquals(2, $stats['files']);
        $this->assertGreaterThan(0, $stats['size']);
    }

    public function test_file_uses_sha256_prefix_directory(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('testkey', 'value');

        // The file should be stored in a 2-char prefix subdirectory
        $hash = hash('sha256', 'testkey');
        $prefix = substr($hash, 0, 2);
        $expectedDir = $this->cacheDir . DIRECTORY_SEPARATOR . $prefix;

        $this->assertDirectoryExists($expectedDir);
    }

    public function test_default_ttl_applied(): void
    {
        $cache = new FileCache($this->cacheDir, defaultTtl: 3600);

        $cache->set('key', 'value');
        $this->assertEquals('value', $cache->get('key'));

        // Overwrite with an already-expired entry to verify TTL is applied
        $this->writeExpiredCacheEntry('key', 'value');

        $this->assertNull($cache->get('key'));
    }

    public function test_overwrite_existing_key(): void
    {
        $cache = new FileCache($this->cacheDir);

        $cache->set('key', 'value1');
        $cache->set('key', 'value2');

        $this->assertEquals('value2', $cache->get('key'));
    }
}
