<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\SwarmIcons;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        SwarmIcons::reset();
    }

    public function test_icon_function_exists(): void
    {
        $this->assertTrue(\function_exists('swarm_icon'));
    }

    public function test_icon_returns_icon(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $icon = swarm_icon('custom:home');
        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertStringContainsString('svg', $icon->toHtml());
    }

    public function test_icon_with_attributes(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $icon = swarm_icon('custom:home', ['class' => 'w-6 h-6']);
        $this->assertStringContainsString('class=', $icon->toHtml());
    }

    public function test_icon_throws_when_no_manager(): void
    {
        $this->expectException(\Frostybee\SwarmIcons\Exception\SwarmIconsException::class);

        swarm_icon('custom:home');
    }

    public function test_swarm_sprite_function_exists(): void
    {
        $this->assertTrue(\function_exists('swarm_sprite'));
    }

    public function test_swarm_sprite_returns_use_reference(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $html = swarm_sprite('custom:home', ['class' => 'w-6 h-6']);

        $this->assertStringContainsString('<use href="#custom-home"/>', $html);
        $this->assertStringContainsString('class="w-6 h-6"', $html);
    }

    public function test_swarm_sprites_function_exists(): void
    {
        $this->assertTrue(\function_exists('swarm_sprites'));
    }

    public function test_swarm_sprites_renders_sprite_sheet(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        swarm_sprite('custom:home');
        $html = swarm_sprites();

        $this->assertStringContainsString('style="display:none"', $html);
        $this->assertStringContainsString('<symbol id="custom-home"', $html);
    }

    public function test_swarm_sprites_returns_empty_when_no_icons(): void
    {
        $manager = new IconManager();

        SwarmIcons::setManager($manager);

        $this->assertEquals('', swarm_sprites());
    }

    public function test_sicon_function_exists(): void
    {
        $this->assertTrue(\function_exists('sicon'));
    }

    public function test_sicon_returns_same_as_swarm_icon(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $icon1 = swarm_icon('custom:home');
        $icon2 = sicon('custom:home');

        $this->assertEquals($icon1->toHtml(), $icon2->toHtml());
    }
}
