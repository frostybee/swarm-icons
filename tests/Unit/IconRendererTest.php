<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\IconRenderer;
use PHPUnit\Framework\TestCase;

class IconRendererTest extends TestCase
{
    public function test_render_with_no_attributes(): void
    {
        $renderer = new IconRenderer();
        $icon = new Icon('<path d="M10 10"/>');

        $rendered = $renderer->render($icon);

        $html = $rendered->toHtml();
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_render_merges_global_defaults(): void
    {
        $renderer = new IconRenderer(['class' => 'icon', 'fill' => 'none']);
        $icon = new Icon('<path d="M10 10"/>');

        $rendered = $renderer->render($icon);

        $this->assertEquals('icon', $rendered->getAttribute('class'));
        $this->assertEquals('none', $rendered->getAttribute('fill'));
    }

    public function test_render_merges_prefix_attributes(): void
    {
        $renderer = new IconRenderer([], ['tabler' => ['stroke-width' => '1.5']]);
        $icon = new Icon('<path d="M10 10"/>');

        $rendered = $renderer->render($icon, 'tabler');

        $this->assertEquals('1.5', $rendered->getAttribute('stroke-width'));
    }

    public function test_render_merges_caller_attributes(): void
    {
        $renderer = new IconRenderer();
        $icon = new Icon('<path d="M10 10"/>');

        $rendered = $renderer->render($icon, null, ['width' => '32', 'height' => '32']);

        $this->assertEquals('32', $rendered->getAttribute('width'));
        $this->assertEquals('32', $rendered->getAttribute('height'));
    }

    public function test_attribute_merge_order(): void
    {
        // Setup: icon has width=16, global has width=24, caller has width=32
        $renderer = new IconRenderer(['width' => '24']);
        $icon = new Icon('<path d="M10 10"/>', ['width' => '16']);

        $rendered = $renderer->render($icon, null, ['width' => '32']);

        // Caller attributes should win
        $this->assertEquals('32', $rendered->getAttribute('width'));
    }

    public function test_css_classes_are_merged(): void
    {
        $renderer = new IconRenderer(['class' => 'icon']);
        $icon = new Icon('<path d="M10 10"/>', ['class' => 'base']);

        $rendered = $renderer->render($icon, null, ['class' => 'custom']);

        $class = $rendered->getAttribute('class');
        $this->assertStringContainsString('base', $class);
        $this->assertStringContainsString('icon', $class);
        $this->assertStringContainsString('custom', $class);
    }

    public function test_aria_hidden_for_decorative_icons(): void
    {
        $renderer = new IconRenderer();
        $icon = new Icon('<path d="M10 10"/>');

        $rendered = $renderer->render($icon);

        $this->assertEquals('true', $rendered->getAttribute('aria-hidden'));
    }

    public function test_role_img_for_labeled_icons_with_aria_label(): void
    {
        $renderer = new IconRenderer();
        $icon = new Icon('<path d="M10 10"/>');

        $rendered = $renderer->render($icon, null, ['aria-label' => 'Home']);

        $this->assertEquals('img', $rendered->getAttribute('role'));
        $this->assertEquals('Home', $rendered->getAttribute('aria-label'));
        $this->assertNull($rendered->getAttribute('aria-hidden'));
    }

    public function test_role_img_for_labeled_icons_with_aria_labelledby(): void
    {
        $renderer = new IconRenderer();
        $icon = new Icon('<path d="M10 10"/>');

        $rendered = $renderer->render($icon, null, ['aria-labelledby' => 'home-label']);

        $this->assertEquals('img', $rendered->getAttribute('role'));
        $this->assertNull($rendered->getAttribute('aria-hidden'));
    }

    public function test_set_and_get_default_attributes(): void
    {
        $renderer = new IconRenderer();
        $renderer->setDefaultAttributes(['class' => 'icon', 'fill' => 'none']);

        $this->assertEquals(['class' => 'icon', 'fill' => 'none'], $renderer->getDefaultAttributes());
    }

    public function test_set_and_get_prefix_attributes(): void
    {
        $renderer = new IconRenderer();
        $renderer->setPrefixAttributes('tabler', ['stroke-width' => '1.5']);

        $this->assertEquals(['stroke-width' => '1.5'], $renderer->getPrefixAttributes('tabler'));
        $this->assertEquals([], $renderer->getPrefixAttributes('nonexistent'));
    }

    public function test_get_all_prefix_attributes(): void
    {
        $renderer = new IconRenderer([], [
            'tabler' => ['stroke-width' => '1.5'],
            'heroicons' => ['fill' => 'currentColor'],
        ]);

        $all = $renderer->getAllPrefixAttributes();

        $this->assertCount(2, $all);
        $this->assertEquals(['stroke-width' => '1.5'], $all['tabler']);
        $this->assertEquals(['fill' => 'currentColor'], $all['heroicons']);
    }

    public function test_null_attribute_values_are_skipped(): void
    {
        $renderer = new IconRenderer();
        $icon = new Icon('<path d="M10 10"/>', ['width' => '24']);

        $rendered = $renderer->render($icon, null, ['width' => null, 'height' => '32']);

        $this->assertEquals('24', $rendered->getAttribute('width')); // Original preserved
        $this->assertEquals('32', $rendered->getAttribute('height'));
    }
}
