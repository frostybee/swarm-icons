<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use DOMDocument;
use DOMElement;
use Frostybee\SwarmIcons\Exception\ProviderException;

/**
 * Utility for parsing SVG files and extracting content and attributes.
 */
class SvgParser
{
    /**
     * Parse SVG content and extract inner content and attributes.
     *
     * @param string $svgContent The SVG content to parse
     * @return array{content: string, attributes: array<string, string>}
     * @throws ProviderException
     */
    public static function parse(string $svgContent): array
    {
        $svgContent = trim($svgContent);

        if (empty($svgContent)) {
            throw new ProviderException('SVG content is empty');
        }

        // Try DOMDocument first (most reliable)
        try {
            $result = self::parseWithDom($svgContent);
        } catch (\Throwable $e) {
            // Fallback to regex parsing
            $result = self::parseWithRegex($svgContent);
        }

        // Sanitize inner content to prevent XSS
        $result['content'] = self::sanitizeContent($result['content']);

        return $result;
    }

    /**
     * Parse SVG using DOMDocument.
     *
     * @param string $svgContent
     * @return array{content: string, attributes: array<string, string>}
     * @throws ProviderException
     */
    private static function parseWithDom(string $svgContent): array
    {
        $dom = new DOMDocument();
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = true;

        // Suppress warnings for malformed HTML
        $previousValue = libxml_use_internal_errors(true);

        $loaded = $dom->loadXML($svgContent, LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors();
        libxml_use_internal_errors($previousValue);

        if (!$loaded) {
            throw new ProviderException('Failed to parse SVG with DOMDocument');
        }

        $svg = $dom->documentElement;

        if (!$svg instanceof DOMElement || $svg->nodeName !== 'svg') {
            throw new ProviderException('Root element is not an SVG element');
        }

        // Extract attributes
        $attributes = [];
        foreach ($svg->attributes as $attr) {
            $attributes[$attr->name] = $attr->value;
        }

        // Extract inner content
        $innerContent = '';
        foreach ($svg->childNodes as $child) {
            $innerContent .= $dom->saveXML($child);
        }

        return [
            'content' => trim($innerContent),
            'attributes' => $attributes,
        ];
    }

    /**
     * Parse SVG using regex (fallback).
     *
     * @param string $svgContent
     * @return array{content: string, attributes: array<string, string>}
     * @throws ProviderException
     */
    private static function parseWithRegex(string $svgContent): array
    {
        // Match opening SVG tag with attributes
        if (!preg_match('/<svg([^>]*)>(.*)<\/svg>/is', $svgContent, $matches)) {
            throw new ProviderException('Invalid SVG format: no SVG tags found');
        }

        $attributesString = $matches[1];
        $innerContent = $matches[2];

        // Parse attributes
        $attributes = [];
        if (preg_match_all('/(\w+(?:[:\-]\w+)*)=["\']([^"\']*)["\']/', $attributesString, $attrMatches, PREG_SET_ORDER)) {
            foreach ($attrMatches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }

        return [
            'content' => trim($innerContent),
            'attributes' => $attributes,
        ];
    }

    /**
     * Sanitize SVG inner content by removing script tags and event handlers.
     *
     * @param string $content
     * @return string
     */
    private static function sanitizeContent(string $content): string
    {
        // Remove <script> tags and their content
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content) ?? $content;

        // Remove event handler attributes (on*) - handles quoted, unquoted, and mismatched quotes
        $content = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $content) ?? $content;

        // Neutralize javascript: URIs in href/src/xlink:href
        $content = preg_replace('/\b(href|src|xlink:href)\s*=\s*["\']?\s*javascript:/i', '$1="#"', $content) ?? $content;

        return $content;
    }

    /**
     * Parse SVG from a file.
     *
     * @param string $filePath
     * @return array{content: string, attributes: array<string, string>}
     * @throws ProviderException
     */
    public static function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new ProviderException("SVG file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new ProviderException("SVG file is not readable: {$filePath}");
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new ProviderException("Failed to read SVG file: {$filePath}");
        }

        return self::parse($content);
    }

    /**
     * Validate if a string is valid SVG.
     *
     * @param string $content
     * @return bool
     */
    public static function isValidSvg(string $content): bool
    {
        try {
            self::parse($content);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
