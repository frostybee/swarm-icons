<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Exception\ProviderException;
use Frostybee\SwarmIcons\Icon;
use PHPUnit\Framework\TestCase;

class IconTest extends TestCase
{
    public function test_constructor(): void
    {
        $icon = new Icon('<path d="M10 10"/>', ['width' => '24', 'height' => '24']);

        $this->assertEquals('<path d="M10 10"/>', $icon->getContent());
        $this->assertEquals(['width' => '24', 'height' => '24'], $icon->getAttributes());
    }

    public function test_from_string(): void
    {
        $svg = '<svg width="24" height="24"><path d="M10 10"/></svg>';

        $icon = Icon::fromString($svg);

        $this->assertStringContainsString('path', $icon->getContent());
        $this->assertEquals('24', $icon->getAttribute('width'));
    }

    public function test_from_file(): void
    {
        $filePath = __DIR__ . '/../Fixtures/icons/home.svg';

        $icon = Icon::fromFile($filePath);

        $this->assertStringContainsString('path', $icon->getContent());
        $this->assertEquals('24', $icon->getAttribute('width'));
    }

    public function test_from_iconify_data(): void
    {
        $data = [
            'body' => '<path d="M10 10"/>',
            'width' => 24,
            'height' => 24,
            'left' => 0,
            'top' => 0,
        ];

        $icon = Icon::fromIconifyData($data);

        $this->assertEquals('<path d="M10 10"/>', $icon->getContent());
        $this->assertEquals('0 0 24 24', $icon->getAttribute('viewBox'));
        $this->assertEquals('24', $icon->getAttribute('width'));
        $this->assertEquals('24', $icon->getAttribute('height'));
    }

    public function test_from_iconify_data_missing_body_throws_exception(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Invalid Iconify data');

        Icon::fromIconifyData(['width' => 24]);
    }

    public function test_get_attribute(): void
    {
        $icon = new Icon('', ['width' => '24', 'height' => '24']);

        $this->assertEquals('24', $icon->getAttribute('width'));
        $this->assertNull($icon->getAttribute('nonexistent'));
        $this->assertEquals('default', $icon->getAttribute('nonexistent', 'default'));
    }

    public function test_has_attribute(): void
    {
        $icon = new Icon('', ['width' => '24']);

        $this->assertTrue($icon->hasAttribute('width'));
        $this->assertFalse($icon->hasAttribute('height'));
    }

    public function test_attr_method_returns_new_instance(): void
    {
        $icon1 = new Icon('', ['width' => '24']);
        $icon2 = $icon1->attr(['height' => '24']);

        $this->assertNotSame($icon1, $icon2);
        $this->assertFalse($icon1->hasAttribute('height'));
        $this->assertTrue($icon2->hasAttribute('height'));
        $this->assertEquals('24', $icon2->getAttribute('width'));
    }

    public function test_attr_method_merge_mode(): void
    {
        $icon1 = new Icon('', ['width' => '24', 'height' => '24']);
        $icon2 = $icon1->attr(['fill' => 'red'], merge: true);

        $this->assertEquals('24', $icon2->getAttribute('width'));
        $this->assertEquals('red', $icon2->getAttribute('fill'));
    }

    public function test_attr_method_replace_mode(): void
    {
        $icon1 = new Icon('', ['width' => '24', 'height' => '24']);
        $icon2 = $icon1->attr(['fill' => 'red'], merge: false);

        $this->assertNull($icon2->getAttribute('width'));
        $this->assertEquals('red', $icon2->getAttribute('fill'));
    }

    public function test_class_method_merges_classes(): void
    {
        $icon1 = new Icon('', ['class' => 'icon']);
        $icon2 = $icon1->class('w-6 h-6');

        $this->assertEquals('icon w-6 h-6', $icon2->getAttribute('class'));
    }

    public function test_class_method_with_array(): void
    {
        $icon1 = new Icon('', ['class' => 'icon']);
        $icon2 = $icon1->class(['w-6', 'h-6', 'text-blue-500']);

        $this->assertEquals('icon w-6 h-6 text-blue-500', $icon2->getAttribute('class'));
    }

    public function test_size_method(): void
    {
        $icon1 = new Icon('');
        $icon2 = $icon1->size(32);

        $this->assertEquals('32', $icon2->getAttribute('width'));
        $this->assertEquals('32', $icon2->getAttribute('height'));
    }

    public function test_stroke_width_method(): void
    {
        $icon = (new Icon(''))->strokeWidth(1.5);

        $this->assertEquals('1.5', $icon->getAttribute('stroke-width'));
    }

    public function test_fill_method(): void
    {
        $icon = (new Icon(''))->fill('red');

        $this->assertEquals('red', $icon->getAttribute('fill'));
    }

    public function test_stroke_method(): void
    {
        $icon = (new Icon(''))->stroke('blue');

        $this->assertEquals('blue', $icon->getAttribute('stroke'));
    }

    public function test_to_html(): void
    {
        $icon = new Icon('<path d="M10 10"/>', ['width' => '24', 'height' => '24']);

        $html = $icon->toHtml();

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('width="24"', $html);
        $this->assertStringContainsString('height="24"', $html);
        $this->assertStringContainsString('<path d="M10 10"/>', $html);
        $this->assertStringContainsString('</svg>', $html);
    }

    public function test_to_string(): void
    {
        $icon = new Icon('<path d="M10 10"/>', ['width' => '24']);

        $this->assertStringContainsString('<svg', (string) $icon);
        $this->assertStringContainsString('width="24"', (string) $icon);
    }

    public function test_serialization(): void
    {
        $icon1 = new Icon('<path d="M10 10"/>', ['width' => '24']);

        $serialized = serialize($icon1);
        $icon2 = unserialize($serialized);

        $this->assertEquals($icon1->getContent(), $icon2->getContent());
        $this->assertEquals($icon1->getAttributes(), $icon2->getAttributes());
        $this->assertEquals($icon1->toHtml(), $icon2->toHtml());
    }

    public function test_html_escaping_in_attributes(): void
    {
        $icon = new Icon('', ['data-value' => '<script>alert("xss")</script>']);

        $html = $icon->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
