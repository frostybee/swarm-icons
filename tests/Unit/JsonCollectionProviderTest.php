<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Exception\ProviderException;
use Frostybee\SwarmIcons\Provider\JsonCollectionProvider;
use PHPUnit\Framework\TestCase;

class JsonCollectionProviderTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = __DIR__ . '/../Fixtures/test-collection.json';
    }

    public function test_get_returns_icon(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $icon = $provider->get('home');

        $this->assertNotNull($icon);
        $this->assertStringContainsString('path', $icon->getContent());
    }

    public function test_get_returns_null_for_nonexistent_icon(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $this->assertNull($provider->get('nonexistent'));
    }

    public function test_has_returns_true_for_existing_icon(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $this->assertTrue($provider->has('home'));
        $this->assertTrue($provider->has('user'));
        $this->assertTrue($provider->has('star'));
    }

    public function test_has_returns_false_for_nonexistent_icon(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $this->assertFalse($provider->has('nonexistent'));
    }

    public function test_has_returns_true_for_alias(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $this->assertTrue($provider->has('house'));
        $this->assertTrue($provider->has('person'));
    }

    public function test_all_returns_all_icon_and_alias_names(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $names = iterator_to_array($provider->all());

        $this->assertContains('home', $names);
        $this->assertContains('user', $names);
        $this->assertContains('star', $names);
        $this->assertContains('house', $names);
        $this->assertContains('person', $names);
        $this->assertContains('chained-alias', $names);
    }

    public function test_alias_resolves_to_parent_icon(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $house = $provider->get('house');
        $home = $provider->get('home');

        $this->assertNotNull($house);
        $this->assertNotNull($home);
        $this->assertSame($home->getContent(), $house->getContent());
    }

    public function test_chained_alias_resolves_correctly(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $chained = $provider->get('chained-alias');
        $home = $provider->get('home');

        $this->assertNotNull($chained);
        $this->assertNotNull($home);
        $this->assertSame($home->getContent(), $chained->getContent());
    }

    public function test_root_defaults_applied_when_icon_has_no_dimensions(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $icon = $provider->get('home');

        $this->assertNotNull($icon);
        // Root defaults are 24x24, so viewBox should contain those
        $this->assertSame('0 0 24 24', $icon->getAttribute('viewBox'));
    }

    public function test_icon_level_dimensions_override_root_defaults(): void
    {
        $provider = new JsonCollectionProvider($this->fixturePath);

        $icon = $provider->get('user');

        $this->assertNotNull($icon);
        // User icon has explicit 32x32
        $this->assertSame('0 0 32 32', $icon->getAttribute('viewBox'));
    }

    public function test_throws_exception_for_missing_file(): void
    {
        $provider = new JsonCollectionProvider('/nonexistent/file.json');

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('does not exist');

        $provider->get('home');
    }

    public function test_throws_exception_for_invalid_json(): void
    {
        $invalidPath = tempnam(sys_get_temp_dir(), 'swarm_test_');
        file_put_contents($invalidPath, 'not valid json{{{');

        try {
            $provider = new JsonCollectionProvider($invalidPath);

            $this->expectException(ProviderException::class);
            $this->expectExceptionMessage('Invalid JSON');

            $provider->get('home');
        } finally {
            unlink($invalidPath);
        }
    }

    public function test_throws_exception_for_json_missing_icons_key(): void
    {
        $noIconsPath = tempnam(sys_get_temp_dir(), 'swarm_test_');
        file_put_contents($noIconsPath, json_encode(['prefix' => 'test']));

        try {
            $provider = new JsonCollectionProvider($noIconsPath);

            $this->expectException(ProviderException::class);
            $this->expectExceptionMessage("missing 'icons' key");

            $provider->get('home');
        } finally {
            unlink($noIconsPath);
        }
    }

    public function test_lazy_loading_does_not_parse_on_construction(): void
    {
        // This should NOT throw even though the file doesn't exist yet
        $provider = new JsonCollectionProvider('/nonexistent/file.json');

        // No exception until we actually access data
        $this->assertInstanceOf(JsonCollectionProvider::class, $provider);
    }
}
