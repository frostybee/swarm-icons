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
}
