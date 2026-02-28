<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Util;

use Frostybee\SwarmIcons\Util\ManifestManager;
use PHPUnit\Framework\TestCase;

class ManifestManagerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/swarm-icons-manifest-test-' . uniqid();
        @mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);
    }

    private function createManager(?string $content = null): ManifestManager
    {
        $path = $this->tempDir . '/swarm-icons.json';
        if ($content !== null) {
            file_put_contents($path, $content);
        }

        return new ManifestManager($path);
    }

    public function test_load_prefixes_returns_null_when_no_manifest(): void
    {
        $manager = $this->createManager();

        $this->assertNull($manager->loadPrefixes());
    }

    public function test_load_prefixes_reads_json_sets_array(): void
    {
        $manager = $this->createManager(json_encode([
            'json-sets' => ['mdi', 'bi', 'tabler'],
        ], JSON_THROW_ON_ERROR));

        $result = $manager->loadPrefixes();

        $this->assertSame(['mdi', 'bi', 'tabler'], $result);
    }

    public function test_load_prefixes_returns_null_for_empty_sets(): void
    {
        $manager = $this->createManager(json_encode([
            'json-sets' => [],
        ], JSON_THROW_ON_ERROR));

        $this->assertNull($manager->loadPrefixes());
    }

    public function test_load_versions_returns_empty_when_no_versions_key(): void
    {
        $manager = $this->createManager(json_encode([
            'json-sets' => ['mdi'],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame([], $manager->loadVersions());
    }

    public function test_load_versions_returns_version_map(): void
    {
        $manager = $this->createManager(json_encode([
            'json-sets' => ['mdi', 'bi'],
            'versions' => ['mdi' => '7.4.47', 'bi' => '1.11.3'],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(['mdi' => '7.4.47', 'bi' => '1.11.3'], $manager->loadVersions());
    }

    public function test_load_versions_returns_empty_when_no_manifest(): void
    {
        $manager = $this->createManager();

        $this->assertSame([], $manager->loadVersions());
    }

    public function test_save_creates_manifest_with_prefixes_and_versions(): void
    {
        $manager = $this->createManager();

        $manager->save(['mdi', 'bi'], ['mdi' => '7.4.47', 'bi' => '1.11.3']);

        $this->assertSame(['bi', 'mdi'], $manager->loadPrefixes());
        $this->assertSame(['bi' => '1.11.3', 'mdi' => '7.4.47'], $manager->loadVersions());
    }

    public function test_save_creates_manifest_without_versions(): void
    {
        $manager = $this->createManager();

        $manager->save(['mdi', 'bi']);

        $this->assertSame(['bi', 'mdi'], $manager->loadPrefixes());
        $this->assertSame([], $manager->loadVersions());
    }

    public function test_save_merges_with_existing_manifest(): void
    {
        $manager = $this->createManager(json_encode([
            'json-sets' => ['mdi'],
            'versions' => ['mdi' => '7.4.47'],
        ], JSON_THROW_ON_ERROR));

        $manager->save(['bi'], ['bi' => '1.11.3']);

        $this->assertSame(['bi', 'mdi'], $manager->loadPrefixes());
        $this->assertSame(['bi' => '1.11.3', 'mdi' => '7.4.47'], $manager->loadVersions());
    }

    public function test_save_preserves_existing_versions_on_prefix_only_save(): void
    {
        $manager = $this->createManager(json_encode([
            'json-sets' => ['mdi'],
            'versions' => ['mdi' => '7.4.47'],
        ], JSON_THROW_ON_ERROR));

        $manager->save(['tabler']);

        $this->assertSame(['mdi', 'tabler'], $manager->loadPrefixes());
        $this->assertSame(['mdi' => '7.4.47'], $manager->loadVersions());
    }

    public function test_save_updates_existing_version(): void
    {
        $manager = $this->createManager(json_encode([
            'json-sets' => ['mdi'],
            'versions' => ['mdi' => '7.4.47'],
        ], JSON_THROW_ON_ERROR));

        $manager->save(['mdi'], ['mdi' => '7.5.0']);

        $this->assertSame(['mdi'], $manager->loadPrefixes());
        $this->assertSame(['mdi' => '7.5.0'], $manager->loadVersions());
    }

    public function test_get_manifest_path(): void
    {
        $path = $this->tempDir . '/swarm-icons.json';
        $manager = new ManifestManager($path);

        $this->assertSame($path, $manager->getManifestPath());
    }

    public function test_load_prefixes_returns_null_for_invalid_json(): void
    {
        $manager = $this->createManager('not valid json');

        $this->assertNull($manager->loadPrefixes());
    }
}
