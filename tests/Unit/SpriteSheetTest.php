<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\SpriteSheet;
use PHPUnit\Framework\TestCase;

class SpriteSheetTest extends TestCase
{
    private string $fixturesPath;

    private IconManager $manager;

    protected function setUp(): void
    {
        $this->fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $this->manager = new IconManager();
        $this->manager->register('test', new DirectoryProvider($this->fixturesPath));
    }

    public function test_use_returns_svg_with_use_reference(): void
    {
        $sprites = new SpriteSheet($this->manager);

        $html = $sprites->use('test:home');

        $this->assertStringContainsString('<use href="#test-home"/>', $html);
        $this->assertStringStartsWith('<svg', $html);
        $this->assertStringEndsWith('</svg>', $html);
    }

    public function test_use_with_attributes(): void
    {
        $sprites = new SpriteSheet($this->manager);

        $html = $sprites->use('test:home', ['class' => 'w-6 h-6']);

        $this->assertStringContainsString('class="w-6 h-6"', $html);
        $this->assertStringContainsString('<use href="#test-home"/>', $html);
    }

    public function test_use_registers_symbol(): void
    {
        $sprites = new SpriteSheet($this->manager);

        $this->assertFalse($sprites->has('test:home'));

        $sprites->use('test:home');

        $this->assertTrue($sprites->has('test:home'));
    }

    public function test_use_same_icon_twice_does_not_duplicate(): void
    {
        $sprites = new SpriteSheet($this->manager);

        $sprites->use('test:home');
        $sprites->use('test:home');

        $this->assertEquals(1, $sprites->count());
    }

    public function test_render_returns_hidden_svg_with_symbols(): void
    {
        $sprites = new SpriteSheet($this->manager);
        $sprites->use('test:home');

        $html = $sprites->render();

        $this->assertStringContainsString('style="display:none"', $html);
        $this->assertStringContainsString('<symbol id="test-home"', $html);
        $this->assertStringContainsString('viewBox=', $html);
        $this->assertStringContainsString('</symbol>', $html);
    }

    public function test_render_empty_returns_empty_string(): void
    {
        $sprites = new SpriteSheet($this->manager);

        $this->assertEquals('', $sprites->render());
    }

    public function test_render_multiple_symbols(): void
    {
        $sprites = new SpriteSheet($this->manager);
        $sprites->use('test:home');
        $sprites->use('test:user');

        $html = $sprites->render();

        $this->assertStringContainsString('<symbol id="test-home"', $html);
        $this->assertStringContainsString('<symbol id="test-user"', $html);
        $this->assertEquals(2, $sprites->count());
    }

    public function test_to_string_calls_render(): void
    {
        $sprites = new SpriteSheet($this->manager);
        $sprites->use('test:home');

        $this->assertEquals($sprites->render(), (string) $sprites);
    }

    public function test_reset_clears_symbols(): void
    {
        $sprites = new SpriteSheet($this->manager);
        $sprites->use('test:home');
        $this->assertEquals(1, $sprites->count());

        $sprites->reset();

        $this->assertEquals(0, $sprites->count());
        $this->assertEquals('', $sprites->render());
    }

    public function test_name_to_id_replaces_colons_and_slashes(): void
    {
        $sprites = new SpriteSheet($this->manager);

        $html = $sprites->use('test:home');

        $this->assertStringContainsString('href="#test-home"', $html);
    }
}
