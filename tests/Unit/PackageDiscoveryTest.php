<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Discovery\PackageDiscovery;
use Frostybee\SwarmIcons\IconManager;
use PHPUnit\Framework\TestCase;

class PackageDiscoveryTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../Fixtures';
    }

    public function test_discover_returns_empty_for_missing_vendor_path(): void
    {
        $result = PackageDiscovery::discover('/nonexistent/path/vendor');

        $this->assertSame([], $result);
    }

    public function test_discover_returns_empty_for_missing_installed_json(): void
    {
        $result = PackageDiscovery::discover($this->fixturesPath);

        $this->assertSame([], $result);
    }

    public function test_discover_finds_packages_with_swarm_icons_extra(): void
    {
        $vendorPath = $this->createMockVendor([
            [
                'name' => 'frostybee/swarm-icons-tabler',
                'extra' => [
                    'swarm-icons' => [
                        'prefix' => 'tabler',
                        'provider-class' => 'Frostybee\\SwarmIcons\\Tabler\\TablerIconSet',
                    ],
                ],
            ],
        ]);

        $result = PackageDiscovery::discover($vendorPath);

        $this->assertCount(1, $result);
        $this->assertSame('tabler', $result[0]['prefix']);
        $this->assertSame('Frostybee\\SwarmIcons\\Tabler\\TablerIconSet', $result[0]['provider-class']);
        $this->assertSame('frostybee/swarm-icons-tabler', $result[0]['package']);
    }

    public function test_discover_ignores_packages_without_swarm_icons_extra(): void
    {
        $vendorPath = $this->createMockVendor([
            [
                'name' => 'some/other-package',
                'extra' => ['branch-alias' => ['dev-main' => '1.0-dev']],
            ],
            [
                'name' => 'frostybee/swarm-icons-tabler',
                'extra' => [
                    'swarm-icons' => [
                        'prefix' => 'tabler',
                        'provider-class' => 'Frostybee\\SwarmIcons\\Tabler\\TablerIconSet',
                    ],
                ],
            ],
        ]);

        $result = PackageDiscovery::discover($vendorPath);

        $this->assertCount(1, $result);
        $this->assertSame('tabler', $result[0]['prefix']);
    }

    public function test_discover_ignores_entries_with_missing_prefix(): void
    {
        $vendorPath = $this->createMockVendor([
            [
                'name' => 'frostybee/swarm-icons-bad',
                'extra' => [
                    'swarm-icons' => [
                        'provider-class' => 'Some\\Class',
                        // 'prefix' intentionally missing
                    ],
                ],
            ],
        ]);

        $result = PackageDiscovery::discover($vendorPath);

        $this->assertSame([], $result);
    }

    public function test_discover_ignores_entries_with_missing_provider_class(): void
    {
        $vendorPath = $this->createMockVendor([
            [
                'name' => 'frostybee/swarm-icons-bad',
                'extra' => [
                    'swarm-icons' => [
                        'prefix' => 'tabler',
                        // 'provider-class' intentionally missing
                    ],
                ],
            ],
        ]);

        $result = PackageDiscovery::discover($vendorPath);

        $this->assertSame([], $result);
    }

    public function test_discover_handles_composer_v2_packages_key(): void
    {
        $vendorPath = $this->createMockVendor(
            [
                [
                    'name' => 'frostybee/swarm-icons-tabler',
                    'extra' => [
                        'swarm-icons' => [
                            'prefix' => 'tabler',
                            'provider-class' => 'Frostybee\\SwarmIcons\\Tabler\\TablerIconSet',
                        ],
                    ],
                ],
            ],
            wrapInPackagesKey: true,
        );

        $result = PackageDiscovery::discover($vendorPath);

        $this->assertCount(1, $result);
        $this->assertSame('tabler', $result[0]['prefix']);
    }

    public function test_discover_finds_multiple_packages(): void
    {
        $vendorPath = $this->createMockVendor([
            [
                'name' => 'frostybee/swarm-icons-tabler',
                'extra' => [
                    'swarm-icons' => [
                        'prefix' => 'tabler',
                        'provider-class' => 'Frostybee\\SwarmIcons\\Tabler\\TablerIconSet',
                    ],
                ],
            ],
            [
                'name' => 'frostybee/swarm-icons-lucide',
                'extra' => [
                    'swarm-icons' => [
                        'prefix' => 'lucide',
                        'provider-class' => 'Frostybee\\SwarmIcons\\Lucide\\LucideIconSet',
                    ],
                ],
            ],
        ]);

        $result = PackageDiscovery::discover($vendorPath);

        $this->assertCount(2, $result);
        $prefixes = array_column($result, 'prefix');
        $this->assertContains('tabler', $prefixes);
        $this->assertContains('lucide', $prefixes);
    }

    public function test_register_all_skips_nonexistent_classes(): void
    {
        $vendorPath = $this->createMockVendor([
            [
                'name' => 'frostybee/swarm-icons-fake',
                'extra' => [
                    'swarm-icons' => [
                        'prefix' => 'fake',
                        'provider-class' => 'This\\Class\\Does\\Not\\Exist',
                    ],
                ],
            ],
        ]);

        $manager = new IconManager();

        // Should not throw; just silently skip
        PackageDiscovery::registerAll($manager, $vendorPath);

        $this->assertFalse($manager->hasProvider('fake'));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Create a temporary vendor directory with a mock installed.json.
     *
     * @param array<int, array<string, mixed>> $packages
     */
    private function createMockVendor(array $packages, bool $wrapInPackagesKey = false): string
    {
        $tmpDir = sys_get_temp_dir() . '/swarm-icons-test-' . uniqid();
        mkdir($tmpDir . '/composer', 0o755, true);

        $data = $wrapInPackagesKey ? ['packages' => $packages] : $packages;
        file_put_contents($tmpDir . '/composer/installed.json', json_encode($data));

        // Clean up after test
        register_shutdown_function(static function () use ($tmpDir): void {
            if (is_dir($tmpDir)) {
                array_map('unlink', glob($tmpDir . '/composer/*') ?: []);
                @rmdir($tmpDir . '/composer');
                @rmdir($tmpDir);
            }
        });

        return $tmpDir;
    }
}
