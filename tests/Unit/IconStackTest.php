<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconStack;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use PHPUnit\Framework\TestCase;

class IconStackTest extends TestCase
{
    public function test_empty_stack_renders_empty_svg(): void
    {
        $stack = new IconStack();

        $this->assertEquals('<svg></svg>', $stack->toHtml());
    }

    public function test_push_returns_new_instance(): void
    {
        $stack1 = new IconStack();
        $icon = new Icon('<path d="M0 0"/>', ['viewBox' => '0 0 24 24']);
        $stack2 = $stack1->push($icon);

        $this->assertNotSame($stack1, $stack2);
        $this->assertEquals(0, $stack1->count());
        $this->assertEquals(1, $stack2->count());
    }

    public function test_single_layer_renders_svg_with_g(): void
    {
        $icon = new Icon('<path d="M10 10"/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon);

        $html = $stack->toHtml();

        $this->assertStringContainsString('viewBox="0 0 24 24"', $html);
        $this->assertStringContainsString('<g><path d="M10 10"/></g>', $html);
    }

    public function test_multiple_layers(): void
    {
        $icon1 = new Icon('<circle r="10"/>', ['viewBox' => '0 0 24 24']);
        $icon2 = new Icon('<path d="M5 5"/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon1)->push($icon2);

        $html = $stack->toHtml();

        $this->assertEquals(2, $stack->count());
        $this->assertStringContainsString('<g><circle r="10"/></g>', $html);
        $this->assertStringContainsString('<g><path d="M5 5"/></g>', $html);
    }

    public function test_layer_attributes(): void
    {
        $icon = new Icon('<path d="M0 0"/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon, ['fill' => 'red', 'transform' => 'scale(0.5)']);

        $html = $stack->toHtml();

        $this->assertStringContainsString('fill="red"', $html);
        $this->assertStringContainsString('transform="scale(0.5)"', $html);
    }

    public function test_size_method(): void
    {
        $icon = new Icon('<path/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon)->size(48);

        $html = $stack->toHtml();

        $this->assertStringContainsString('width="48"', $html);
        $this->assertStringContainsString('height="48"', $html);
    }

    public function test_class_method(): void
    {
        $icon = new Icon('<path/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon)->class('badge-icon');

        $html = $stack->toHtml();

        $this->assertStringContainsString('class="badge-icon"', $html);
    }

    public function test_class_method_merges(): void
    {
        $icon = new Icon('<path/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon)->class('icon')->class('large');

        $html = $stack->toHtml();

        $this->assertStringContainsString('class="icon large"', $html);
    }

    public function test_attr_method(): void
    {
        $icon = new Icon('<path/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon)->attr(['data-stack' => 'true']);

        $html = $stack->toHtml();

        $this->assertStringContainsString('data-stack="true"', $html);
    }

    public function test_to_string(): void
    {
        $icon = new Icon('<path/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon);

        $this->assertEquals($stack->toHtml(), (string) $stack);
    }

    public function test_uses_first_layer_viewbox(): void
    {
        $icon1 = new Icon('<path/>', ['viewBox' => '0 0 32 32']);
        $icon2 = new Icon('<path/>', ['viewBox' => '0 0 16 16']);
        $stack = (new IconStack())->push($icon1)->push($icon2);

        $html = $stack->toHtml();

        $this->assertStringContainsString('viewBox="0 0 32 32"', $html);
    }

    public function test_manager_stack_factory(): void
    {
        $manager = new IconManager();
        $fixturesPath = \dirname(__DIR__) . '/Fixtures/icons';
        $manager->register('test', new DirectoryProvider($fixturesPath));

        $stack = $manager->stack('test:home', 'test:user');

        $this->assertInstanceOf(IconStack::class, $stack);
        $this->assertEquals(2, $stack->count());

        $html = $stack->toHtml();
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('<g>', $html);
    }

    public function test_html_escaping_in_layer_attributes(): void
    {
        $icon = new Icon('<path/>', ['viewBox' => '0 0 24 24']);
        $stack = (new IconStack())->push($icon, ['data-value' => '<script>']);

        $html = $stack->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
