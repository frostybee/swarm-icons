<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use Frostybee\SwarmIcons\Exception\ProviderException;
use Stringable;

/**
 * Immutable SVG icon value object.
 *
 * Stores SVG inner content and attributes separately, providing fluent methods
 * for attribute manipulation that return new instances.
 */
class Icon implements Stringable
{
    /**
     * @param string $content SVG inner content (without <svg> wrapper)
     * @param array<string, string> $attributes SVG element attributes
     */
    public function __construct(
        private string $content,
        private array $attributes = [],
    ) {}

    /**
     * Create an Icon from an SVG file.
     *
     * @throws ProviderException
     */
    public static function fromFile(string $filePath): self
    {
        $parsed = SvgParser::parseFile($filePath);

        return new self($parsed['content'], $parsed['attributes']);
    }

    /**
     * Create an Icon from SVG string content.
     *
     * @param string $svgContent Complete SVG markup
     *
     * @throws ProviderException
     */
    public static function fromString(string $svgContent): self
    {
        $parsed = SvgParser::parse($svgContent);

        return new self($parsed['content'], $parsed['attributes']);
    }

    /**
     * Create an Icon from Iconify API data.
     *
     * @param array<string, mixed> $data Iconify icon data
     *
     * @throws ProviderException
     */
    public static function fromIconifyData(array $data): self
    {
        if (!isset($data['body'])) {
            throw new ProviderException('Invalid Iconify data: missing "body" field');
        }

        $attributes = [];

        // Extract viewBox
        if (isset($data['width']) && isset($data['height'])) {
            $left = $data['left'] ?? 0;
            $top = $data['top'] ?? 0;
            $attributes['viewBox'] = "{$left} {$top} {$data['width']} {$data['height']}";
        }

        // Add width and height if present
        if (isset($data['width'])) {
            $attributes['width'] = (string) $data['width'];
        }
        if (isset($data['height'])) {
            $attributes['height'] = (string) $data['height'];
        }

        return new self((string) $data['body'], $attributes);
    }

    /**
     * Get the inner SVG content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get all attributes.
     *
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get a specific attribute value.
     */
    public function getAttribute(string $name, ?string $default = null): ?string
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Check if an attribute exists.
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Set or merge attributes (returns new instance).
     *
     * @param array<string, bool|float|int|string|null> $attributes
     * @param bool $merge Whether to merge with existing attributes
     */
    public function attr(array $attributes, bool $merge = true): self
    {
        // Filter out null values and convert to strings
        $filtered = [];
        foreach ($attributes as $key => $value) {
            if ($value !== null) {
                $filtered[$key] = $this->normalizeAttributeValue($value);
            }
        }

        $newAttributes = $merge
            ? array_merge($this->attributes, $filtered)
            : $filtered;

        return new self($this->content, $newAttributes);
    }

    /**
     * Add or append CSS classes (returns new instance).
     *
     * @param array<int, string>|string $classes
     */
    public function class(string|array $classes): self
    {
        $classString = \is_array($classes) ? implode(' ', $classes) : $classes;
        $existing = $this->getAttribute('class', '');

        $merged = trim($existing . ' ' . $classString);

        return $this->attr(['class' => $merged]);
    }

    /**
     * Set width and height attributes (returns new instance).
     *
     * @param int|string $size Size value (e.g., '24', '1.5rem', 24)
     */
    public function size(string|int $size): self
    {
        $sizeStr = (string) $size;

        return $this->attr([
            'width' => $sizeStr,
            'height' => $sizeStr,
        ]);
    }

    /**
     * Set stroke-width attribute (returns new instance).
     */
    public function strokeWidth(string|int|float $width): self
    {
        return $this->attr(['stroke-width' => (string) $width]);
    }

    /**
     * Set fill attribute (returns new instance).
     */
    public function fill(string $fill): self
    {
        return $this->attr(['fill' => $fill]);
    }

    /**
     * Set stroke attribute (returns new instance).
     */
    public function stroke(string $stroke): self
    {
        return $this->attr(['stroke' => $stroke]);
    }

    /**
     * Render the icon as an SVG string.
     */
    public function toHtml(): string
    {
        $attributesString = $this->renderAttributes();

        return "<svg{$attributesString}>{$this->content}</svg>";
    }

    /**
     * Render attributes as a string.
     */
    private function renderAttributes(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        $parts = [];
        foreach ($this->attributes as $name => $value) {
            // Skip attributes with invalid names to prevent XSS via crafted keys
            if (!preg_match('/^[a-zA-Z_:][\w:.\-]*$/', $name)) {
                continue;
            }
            $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $parts[] = "{$name}=\"{$escapedValue}\"";
        }

        return ' ' . implode(' ', $parts);
    }

    /**
     * Normalize attribute value to string.
     */
    private function normalizeAttributeValue(string|int|float|bool $value): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Convert to string (calls toHtml()).
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * Serialize for caching.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'content' => $this->content,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * Unserialize from cached data.
     *
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->content = $data['content'];
        $this->attributes = $data['attributes'];
    }
}
