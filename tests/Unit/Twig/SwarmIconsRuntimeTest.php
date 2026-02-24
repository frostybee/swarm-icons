<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Twig;

use Frostybee\SwarmIcons\Exception\IconNotFoundException;
use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\Twig\SwarmIconsRuntime;
use PHPUnit\Framework\TestCase;

class SwarmIconsRuntimeTest extends TestCase
{
    private IconManager $manager;
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = \dirname(__DIR__, 2) . '/Fixtures/icons';
        $this->manager = new IconManager();
        $this->manager->register('custom', new DirectoryProvider($this->fixturesPath));
    }

    public function test_render_icon_returns_html(): void
    {
        $runtime = new SwarmIconsRuntime($this->manager);

        $html = $runtime->renderIcon('custom:home');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('</svg>', $html);
    }

    public function test_render_icon_throws_for_missing_when_not_silent(): void
    {
        $runtime = new SwarmIconsRuntime($this->manager, silentOnMissing: false);

        $this->expectException(IconNotFoundException::class);

        $runtime->renderIcon('custom:nonexistent');
    }

    public function test_render_icon_returns_comment_when_silent(): void
    {
        $runtime = new SwarmIconsRuntime($this->manager, silentOnMissing: true);

        $result = $runtime->renderIcon('custom:nonexistent');

        $this->assertMatchesRegularExpression('/<!--.*nonexistent.*-->/', $result);
    }

    public function test_has_icon_returns_true_for_existing(): void
    {
        $runtime = new SwarmIconsRuntime($this->manager);

        $this->assertTrue($runtime->hasIcon('custom:home'));
    }

    public function test_has_icon_returns_false_for_missing(): void
    {
        $runtime = new SwarmIconsRuntime($this->manager);

        $this->assertFalse($runtime->hasIcon('custom:nonexistent'));
    }

    public function test_get_icon_returns_icon_object(): void
    {
        $runtime = new SwarmIconsRuntime($this->manager);

        $icon = $runtime->getIcon('custom:home');

        $this->assertInstanceOf(Icon::class, $icon);
    }

    public function test_get_icon_returns_null_for_missing(): void
    {
        $runtime = new SwarmIconsRuntime($this->manager);

        $this->assertNull($runtime->getIcon('custom:nonexistent'));
    }
}
