<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use FilesystemIterator;
use Frostybee\SwarmIcons\Cache\NullCache;
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Provider\ChainProvider;
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use Frostybee\SwarmIcons\SwarmIconsConfig;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SwarmIconsConfigTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
    }

    public function test_create_returns_instance(): void
    {
        $config = SwarmIconsConfig::create();

        $this->assertInstanceOf(SwarmIconsConfig::class, $config);
    }

    public function test_build_returns_icon_manager(): void
    {
        $manager = SwarmIconsConfig::create()->build();

        $this->assertInstanceOf(IconManager::class, $manager);
    }

    public function test_add_directory_registers_provider(): void
    {
        $manager = SwarmIconsConfig::create()
            ->addDirectory('custom', $this->fixturesPath)
            ->build();

        $this->assertTrue($manager->has('custom:home'));
    }

    public function test_add_iconify_set_defers_until_build(): void
    {
        $config = SwarmIconsConfig::create()
            ->noCache()
            ->addIconifySet('heroicons');

        // Build should succeed and create the provider
        $manager = $config->build();

        // The provider should be registered (we can't easily test it fetches
        // without HTTP, but we can verify the manager has the prefix)
        $provider = $manager->getProvider('heroicons');
        $this->assertInstanceOf(IconifyProvider::class, $provider);
    }

    public function test_add_hybrid_set_creates_chain_provider(): void
    {
        $manager = SwarmIconsConfig::create()
            ->noCache()
            ->addHybridSet('custom', $this->fixturesPath)
            ->build();

        $provider = $manager->getProvider('custom');
        $this->assertInstanceOf(ChainProvider::class, $provider);
    }

    public function test_default_prefix(): void
    {
        $manager = SwarmIconsConfig::create()
            ->addDirectory('custom', $this->fixturesPath)
            ->defaultPrefix('custom')
            ->build();

        // Should be able to get icons without prefix
        $this->assertTrue($manager->has('home'));
    }

    public function test_cache_path(): void
    {
        $cachePath = sys_get_temp_dir() . '/swarm-icons-config-test-' . uniqid('', true);

        try {
            $manager = SwarmIconsConfig::create()
                ->cachePath($cachePath)
                ->addIconifySet('test')
                ->build();

            $this->assertDirectoryExists($cachePath);
        } finally {
            if (is_dir($cachePath)) {
                $items = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($cachePath, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST,
                );
                foreach ($items as $item) {
                    $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
                }
                @rmdir($cachePath);
            }
        }
    }

    public function test_custom_cache_implementation(): void
    {
        $cache = new NullCache();

        $manager = SwarmIconsConfig::create()
            ->cache($cache)
            ->addIconifySet('test')
            ->build();

        // Should build successfully with custom cache
        $this->assertInstanceOf(IconManager::class, $manager);
    }

    public function test_no_cache(): void
    {
        $manager = SwarmIconsConfig::create()
            ->noCache()
            ->addIconifySet('test')
            ->build();

        $this->assertInstanceOf(IconManager::class, $manager);
    }

    public function test_default_attributes(): void
    {
        $manager = SwarmIconsConfig::create()
            ->addDirectory('custom', $this->fixturesPath)
            ->defaultAttributes(['class' => 'icon'])
            ->build();

        $icon = $manager->get('custom:home');
        $this->assertStringContainsString('class="icon', $icon->toHtml());
    }

    public function test_prefix_attributes(): void
    {
        $manager = SwarmIconsConfig::create()
            ->addDirectory('custom', $this->fixturesPath)
            ->prefixAttributes('custom', ['stroke-width' => '1.5'])
            ->build();

        $icon = $manager->get('custom:home');
        $this->assertStringContainsString('stroke-width="1.5"', $icon->toHtml());
    }

    public function test_fallback_icon(): void
    {
        $manager = SwarmIconsConfig::create()
            ->addDirectory('custom', $this->fixturesPath)
            ->fallbackIcon('custom:home')
            ->build();

        // Getting a nonexistent icon should return the fallback
        $icon = $manager->get('custom:nonexistent');
        $this->assertStringContainsString('svg', $icon->toHtml());
    }

    public function test_discover_json_sets_registers_providers(): void
    {
        $jsonDir = \dirname(__DIR__) . '/Fixtures';

        $manager = SwarmIconsConfig::create()
            ->noCache()
            ->discoverJsonSets($jsonDir)
            ->build();

        // test-collection.json should be registered under prefix "test-collection"
        $this->assertNotNull($manager->getProvider('test-collection'));
    }

    public function test_discover_json_sets_returns_self_for_missing_dir(): void
    {
        $config = SwarmIconsConfig::create();

        $result = $config->discoverJsonSets('/nonexistent/path');

        $this->assertSame($config, $result);
    }

    public function test_fluent_chaining(): void
    {
        $config = SwarmIconsConfig::create();

        $this->assertSame($config, $config->addDirectory('custom', $this->fixturesPath));
        $this->assertSame($config, $config->noCache());
        $this->assertSame($config, $config->defaultPrefix('custom'));
        $this->assertSame($config, $config->defaultAttributes(['class' => 'icon']));
        $this->assertSame($config, $config->prefixAttributes('custom', ['fill' => 'none']));
        $this->assertSame($config, $config->addIconifySet('test'));
        $this->assertSame($config, $config->addHybridSet('hybrid', $this->fixturesPath));
    }
}
