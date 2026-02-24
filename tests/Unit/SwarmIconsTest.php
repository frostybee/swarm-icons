<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Exception\SwarmIconsException;
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\SwarmIcons;
use PHPUnit\Framework\TestCase;

class SwarmIconsTest extends TestCase
{
    protected function tearDown(): void
    {
        SwarmIcons::reset();
    }

    public function test_has_manager_returns_false_initially(): void
    {
        $this->assertFalse(SwarmIcons::hasManager());
    }

    public function test_set_and_get_manager(): void
    {
        $manager = new IconManager();

        SwarmIcons::setManager($manager);

        $this->assertSame($manager, SwarmIcons::getManager());
    }

    public function test_has_manager_returns_true_after_set(): void
    {
        SwarmIcons::setManager(new IconManager());

        $this->assertTrue(SwarmIcons::hasManager());
    }

    public function test_get_manager_throws_when_not_set(): void
    {
        $this->expectException(SwarmIconsException::class);

        SwarmIcons::getManager();
    }

    public function test_reset_clears_manager(): void
    {
        SwarmIcons::setManager(new IconManager());
        $this->assertTrue(SwarmIcons::hasManager());

        SwarmIcons::reset();

        $this->assertFalse(SwarmIcons::hasManager());
    }

    public function test_get_delegates_to_manager(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $icon = SwarmIcons::get('custom:home');
        $this->assertStringContainsString('svg', $icon->toHtml());
    }

    public function test_has_delegates_to_manager(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('custom', new DirectoryProvider($fixturesPath));

        SwarmIcons::setManager($manager);

        $this->assertTrue(SwarmIcons::has('custom:home'));
        $this->assertFalse(SwarmIcons::has('custom:nonexistent'));
    }
}
