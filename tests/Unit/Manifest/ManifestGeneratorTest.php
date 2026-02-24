<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Manifest;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Manifest\ManifestGenerator;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use PHPUnit\Framework\TestCase;

class ManifestGeneratorTest extends TestCase
{
    private string $fixturesPath;
    private ManifestGenerator $generator;

    protected function setUp(): void
    {
        $this->fixturesPath = \dirname(__DIR__, 2) . '/Fixtures/icons';
        $this->generator = new ManifestGenerator();
    }

    public function test_generate_returns_array_with_icon_names(): void
    {
        $manager = new IconManager();
        $manager->register('custom', new DirectoryProvider($this->fixturesPath));

        $manifest = $this->generator->generate($manager);

        $this->assertArrayHasKey('custom', $manifest);
        $this->assertContains('home', $manifest['custom']);
    }

    public function test_generate_sorted_names(): void
    {
        $manager = new IconManager();
        $manager->register('custom', new DirectoryProvider($this->fixturesPath));

        $manifest = $this->generator->generate($manager);
        $icons = $manifest['custom'];

        $sorted = $icons;
        sort($sorted);

        $this->assertEquals($sorted, $icons);
    }

    public function test_generate_with_specific_prefixes(): void
    {
        $manager = new IconManager();
        $manager->register('set-a', new DirectoryProvider($this->fixturesPath));
        $manager->register('set-b', new DirectoryProvider($this->fixturesPath));

        $manifest = $this->generator->generate($manager, ['set-a']);

        $this->assertArrayHasKey('set-a', $manifest);
        $this->assertArrayNotHasKey('set-b', $manifest);
    }

    public function test_to_json_returns_valid_json(): void
    {
        $manager = new IconManager();
        $manager->register('custom', new DirectoryProvider($this->fixturesPath));

        $json = $this->generator->toJson($manager);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('custom', $decoded);
    }

    public function test_to_file_writes_json_file(): void
    {
        $outputPath = sys_get_temp_dir() . '/swarm-icons-manifest-test-' . uniqid('', true) . '/icons.json';

        try {
            $manager = new IconManager();
            $manager->register('custom', new DirectoryProvider($this->fixturesPath));

            $stats = $this->generator->toFile($manager, $outputPath);

            $this->assertFileExists($outputPath);
            $this->assertEquals(1, $stats['prefixes']);
            $this->assertGreaterThan(0, $stats['icons']);

            $content = json_decode(file_get_contents($outputPath), true);
            $this->assertArrayHasKey('custom', $content);
        } finally {
            @unlink($outputPath);
            @rmdir(\dirname($outputPath));
        }
    }

    public function test_generate_empty_manager(): void
    {
        $manager = new IconManager();

        $manifest = $this->generator->generate($manager);

        $this->assertEquals([], $manifest);
    }

    public function test_generate_multiple_prefixes(): void
    {
        $manager = new IconManager();
        $manager->register('set-a', new DirectoryProvider($this->fixturesPath));
        $manager->register('set-b', new DirectoryProvider($this->fixturesPath));

        $manifest = $this->generator->generate($manager);

        $this->assertCount(2, $manifest);
        $this->assertArrayHasKey('set-a', $manifest);
        $this->assertArrayHasKey('set-b', $manifest);
    }
}
