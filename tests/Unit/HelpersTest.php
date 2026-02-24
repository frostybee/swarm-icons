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
        $this->assertTrue(\function_exists('icon'));
    }

    public function test_icon_returns_icon(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $icon = icon('custom:home');
        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertStringContainsString('svg', $icon->toHtml());
    }

    public function test_icon_with_attributes(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $icon = icon('custom:home', ['class' => 'w-6 h-6']);
        $this->assertStringContainsString('class=', $icon->toHtml());
    }

    public function test_icon_throws_when_no_manager(): void
    {
        $this->expectException(\Frostybee\SwarmIcons\Exception\SwarmIconsException::class);

        icon('custom:home');
    }
}
