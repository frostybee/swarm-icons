<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Cache\NullCache;
use PHPUnit\Framework\TestCase;

class NullCacheTest extends TestCase
{
    public function test_get_returns_null_by_default(): void
    {
        $cache = new NullCache();

        $this->assertNull($cache->get('anything'));
    }

    public function test_get_returns_custom_default(): void
    {
        $cache = new NullCache();

        $this->assertEquals('fallback', $cache->get('anything', 'fallback'));
    }

    public function test_set_returns_true(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->set('key', 'value'));
    }

    public function test_delete_returns_true(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->delete('key'));
    }

    public function test_clear_returns_true(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->clear());
    }

    public function test_has_returns_false(): void
    {
        $cache = new NullCache();

        $this->assertFalse($cache->has('anything'));
    }

    public function test_get_multiple_returns_defaults(): void
    {
        $cache = new NullCache();

        $result = $cache->getMultiple(['key1', 'key2'], 'default');

        $this->assertEquals([
            'key1' => 'default',
            'key2' => 'default',
        ], $result);
    }

    public function test_set_multiple_returns_true(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->setMultiple(['key1' => 'val1', 'key2' => 'val2']));
    }

    public function test_delete_multiple_returns_true(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->deleteMultiple(['key1', 'key2']));
    }
}
