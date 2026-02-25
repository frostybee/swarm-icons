<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Exception\ProviderException;
use Frostybee\SwarmIcons\SvgParser;
use PHPUnit\Framework\TestCase;

class SvgParserTest extends TestCase
{
    public function test_parse_valid_svg(): void
    {
        $svg = '<svg width="24" height="24" viewBox="0 0 24 24"><path d="M10 10"/></svg>';

        $result = SvgParser::parse($svg);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('attributes', $result);
        $this->assertStringContainsString('<path d="M10 10"/>', $result['content']);
        $this->assertEquals('24', $result['attributes']['width']);
        $this->assertEquals('24', $result['attributes']['height']);
        $this->assertEquals('0 0 24 24', $result['attributes']['viewBox']);
    }

    public function test_parse_svg_with_namespace(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><circle cx="8" cy="8" r="4"/></svg>';

        $result = SvgParser::parse($svg);

        $this->assertStringContainsString('circle', $result['content']);
        $this->assertEquals('16', $result['attributes']['width']);
    }

    public function test_parse_empty_svg_throws_exception(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('SVG content is empty');

        SvgParser::parse('');
    }

    public function test_parse_invalid_svg_throws_exception(): void
    {
        $this->expectException(ProviderException::class);

        SvgParser::parse('<div>not an svg</div>');
    }

    public function test_parse_file(): void
    {
        $filePath = __DIR__ . '/../Fixtures/icons/home.svg';

        $result = SvgParser::parseFile($filePath);

        $this->assertIsArray($result);
        $this->assertStringContainsString('path', $result['content']);
        $this->assertEquals('24', $result['attributes']['width']);
    }

    public function test_parse_file_not_found_throws_exception(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('SVG file not found');

        SvgParser::parseFile('/nonexistent/file.svg');
    }

    public function test_is_valid_svg(): void
    {
        $validSvg = '<svg width="24" height="24"><path d="M10 10"/></svg>';
        $invalidSvg = '<div>not svg</div>';

        $this->assertTrue(SvgParser::isValidSvg($validSvg));
        $this->assertFalse(SvgParser::isValidSvg($invalidSvg));
        $this->assertFalse(SvgParser::isValidSvg(''));
    }

    public function test_sanitize_strips_xml_comments(): void
    {
        $content = '<!-- Created with Adobe Illustrator --><path d="M0 0"/><!-- end -->';

        $result = SvgParser::sanitizeContent($content);

        $this->assertStringNotContainsString('<!--', $result);
        $this->assertStringNotContainsString('Adobe Illustrator', $result);
        $this->assertStringContainsString('<path d="M0 0"/>', $result);
    }

    public function test_sanitize_strips_title_element(): void
    {
        $content = '<title>Home Icon</title><path d="M0 0"/>';

        $result = SvgParser::sanitizeContent($content);

        $this->assertStringNotContainsString('<title>', $result);
        $this->assertStringNotContainsString('Home Icon', $result);
        $this->assertStringContainsString('<path d="M0 0"/>', $result);
    }

    public function test_sanitize_strips_desc_element(): void
    {
        $content = '<desc>Created with Figma</desc><path d="M0 0"/>';

        $result = SvgParser::sanitizeContent($content);

        $this->assertStringNotContainsString('<desc>', $result);
        $this->assertStringNotContainsString('Figma', $result);
        $this->assertStringContainsString('<path d="M0 0"/>', $result);
    }

    public function test_sanitize_strips_title_and_desc_together(): void
    {
        $content = '<title>Icon</title><desc>A nice icon</desc><path d="M0 0"/>';

        $result = SvgParser::sanitizeContent($content);

        $this->assertStringNotContainsString('title', $result);
        $this->assertStringNotContainsString('desc', $result);
        $this->assertStringContainsString('<path', $result);
    }

    public function test_sanitize_normalizes_whitespace_between_tags(): void
    {
        $content = "<path d=\"M0 0\"/>\n    <circle cx=\"12\" cy=\"12\" r=\"4\"/>\n  ";

        $result = SvgParser::sanitizeContent($content);

        $this->assertStringNotContainsString("\n", $result);
        $this->assertEquals('<path d="M0 0"/><circle cx="12" cy="12" r="4"/>', $result);
    }

    public function test_sanitize_preserves_text_content_inside_tags(): void
    {
        // Text content inside a <text> element must not be collapsed
        $content = '<text x="0" y="12">Hello world</text>';

        $result = SvgParser::sanitizeContent($content);

        $this->assertStringContainsString('Hello world', $result);
    }

    public function test_parse_svg_strips_comments_and_metadata(): void
    {
        $svg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">
              <!-- Created with Inkscape -->
              <title>Home</title>
              <desc>A home icon</desc>
              <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
            </svg>
            SVG;

        $result = SvgParser::parse($svg);

        $this->assertStringNotContainsString('<!--', $result['content']);
        $this->assertStringNotContainsString('<title>', $result['content']);
        $this->assertStringNotContainsString('<desc>', $result['content']);
        $this->assertStringNotContainsString('Inkscape', $result['content']);
        $this->assertStringContainsString('<path', $result['content']);
    }
}
