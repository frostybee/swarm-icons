<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Exception\IconNotFoundException;
use Frostybee\SwarmIcons\Exception\InvalidIconNameException;
use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconRenderer;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use PHPUnit\Framework\TestCase;

class IconManagerTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../Fixtures/icons';
    }

    public function test_register_provider(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);

        $manager->register('test', $provider);

        $this->assertTrue($manager->hasProvider('test'));
        $this->assertSame($provider, $manager->getProvider('test'));
    }

    public function test_register_provider_returns_self(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);

        $result = $manager->register('test', $provider);

        $this->assertSame($manager, $result);
    }

    public function test_get_provider_returns_null_for_unregistered(): void
    {
        $manager = new IconManager();

        $this->assertNull($manager->getProvider('nonexistent'));
        $this->assertFalse($manager->hasProvider('nonexistent'));
    }

    public function test_get_registered_prefixes(): void
    {
        $manager = new IconManager();
        $provider1 = new DirectoryProvider($this->fixturesPath);
        $provider2 = new DirectoryProvider($this->fixturesPath);

        $manager->register('tabler', $provider1);
        $manager->register('heroicons', $provider2);

        $prefixes = $manager->getRegisteredPrefixes();

        $this->assertCount(2, $prefixes);
        $this->assertContains('tabler', $prefixes);
        $this->assertContains('heroicons', $prefixes);
    }

    public function test_set_and_get_default_prefix(): void
    {
        $manager = new IconManager();

        $manager->setDefaultPrefix('tabler');

        $this->assertEquals('tabler', $manager->getDefaultPrefix());
    }

    public function test_set_and_get_fallback_icon(): void
    {
        $manager = new IconManager();

        $manager->setFallbackIcon('tabler:question-mark');

        $this->assertEquals('tabler:question-mark', $manager->getFallbackIcon());
    }

    public function test_get_icon_with_prefix(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $icon = $manager->get('test:home');

        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertStringContainsString('path', $icon->getContent());
    }

    public function test_get_icon_with_default_prefix(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);
        $manager->setDefaultPrefix('test');

        $icon = $manager->get('home');

        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertStringContainsString('path', $icon->getContent());
    }

    public function test_get_icon_without_prefix_and_no_default_throws_exception(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->expectException(InvalidIconNameException::class);
        $this->expectExceptionMessage('no default prefix is set');

        $manager->get('home');
    }

    public function test_get_icon_with_unregistered_provider_throws_exception(): void
    {
        $manager = new IconManager();

        $this->expectException(IconNotFoundException::class);
        $this->expectExceptionMessage('No provider registered for prefix');

        $manager->get('nonexistent:home');
    }

    public function test_get_icon_not_found_throws_exception(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->expectException(IconNotFoundException::class);
        $this->expectExceptionMessage('Icon not found');

        $manager->get('test:nonexistent');
    }

    public function test_get_icon_with_attributes(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $icon = $manager->get('test:home', ['width' => '32', 'height' => '32']);

        $this->assertEquals('32', $icon->getAttribute('width'));
        $this->assertEquals('32', $icon->getAttribute('height'));
    }

    public function test_has_returns_true_for_existing_icon(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->assertTrue($manager->has('test:home'));
    }

    public function test_has_returns_false_for_nonexistent_icon(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->assertFalse($manager->has('test:nonexistent'));
    }

    public function test_has_returns_false_for_unregistered_provider(): void
    {
        $manager = new IconManager();

        $this->assertFalse($manager->has('nonexistent:home'));
    }

    public function test_has_returns_false_for_invalid_name(): void
    {
        $manager = new IconManager();

        $this->assertFalse($manager->has(''));
        $this->assertFalse($manager->has(':'));
        $this->assertFalse($manager->has('::'));
    }

    public function test_get_and_set_renderer(): void
    {
        $manager = new IconManager();
        $renderer = new IconRenderer(['class' => 'icon']);

        $manager->setRenderer($renderer);

        $this->assertSame($renderer, $manager->getRenderer());
    }

    public function test_all_returns_icons_from_provider(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $icons = iterator_to_array($manager->all('test'));

        $this->assertContains('home', $icons);
        $this->assertContains('user', $icons);
    }

    public function test_all_returns_empty_for_unregistered_provider(): void
    {
        $manager = new IconManager();

        $icons = iterator_to_array($manager->all('nonexistent'));

        $this->assertEmpty($icons);
    }

    public function test_invalid_icon_name_format_throws_exception(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->expectException(InvalidIconNameException::class);

        $manager->get('');
    }

    public function test_icon_name_with_empty_prefix_throws_exception(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->expectException(InvalidIconNameException::class);

        $manager->get(':home');
    }

    public function test_icon_name_with_empty_name_throws_exception(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->expectException(InvalidIconNameException::class);

        $manager->get('test:');
    }

    // =========================================================================
    // Alias tests
    // =========================================================================

    public function test_alias_resolves_to_target_icon(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $manager->setAlias('h', 'test:home');

        $icon = $manager->get('h');

        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertStringContainsString('path', $icon->getContent());
    }

    public function test_has_resolves_alias(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $manager->setAlias('h', 'test:home');

        $this->assertTrue($manager->has('h'));
    }

    public function test_alias_does_not_interfere_with_prefixed_names(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $manager->setAlias('h', 'test:home');

        // Direct prefixed name still works
        $icon = $manager->get('test:home');
        $this->assertInstanceOf(Icon::class, $icon);
    }

    public function test_alias_to_nonexistent_icon_throws_not_found(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $manager->setAlias('missing', 'test:nonexistent');

        $this->expectException(IconNotFoundException::class);

        $manager->get('missing');
    }

    public function test_get_aliases_returns_registered_aliases(): void
    {
        $manager = new IconManager();

        $manager->setAlias('h', 'test:home');
        $manager->setAlias('u', 'test:user');

        $aliases = $manager->getAliases();

        $this->assertCount(2, $aliases);
        $this->assertSame('test:home', $aliases['h']);
        $this->assertSame('test:user', $aliases['u']);
    }

    // =========================================================================
    // ignoreNotFound tests
    // =========================================================================

    public function test_ignore_not_found_returns_empty_icon_for_missing_icon(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);
        $manager->setIgnoreNotFound(true);

        $icon = $manager->get('test:nonexistent');

        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertSame('', $icon->getContent());
    }

    public function test_ignore_not_found_returns_empty_icon_for_missing_provider(): void
    {
        $manager = new IconManager();
        $manager->setIgnoreNotFound(true);

        $icon = $manager->get('unregistered:home');

        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertSame('', $icon->getContent());
    }

    public function test_ignore_not_found_still_throws_on_invalid_name(): void
    {
        $manager = new IconManager();
        $manager->setIgnoreNotFound(true);

        $this->expectException(InvalidIconNameException::class);

        $manager->get('');
    }

    public function test_ignore_not_found_disabled_by_default(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);

        $this->assertFalse($manager->getIgnoreNotFound());

        $this->expectException(IconNotFoundException::class);

        $manager->get('test:nonexistent');
    }

    public function test_ignore_not_found_prefers_fallback(): void
    {
        $manager = new IconManager();
        $provider = new DirectoryProvider($this->fixturesPath);
        $manager->register('test', $provider);
        $manager->setFallbackIcon('test:home');
        $manager->setIgnoreNotFound(true);

        $icon = $manager->get('test:nonexistent');

        // Should return the fallback icon, not an empty one
        $this->assertStringContainsString('path', $icon->getContent());
    }
}
