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
     * Applies hFlip, vFlip, and rotate transform properties from the data
     * by wrapping the body in SVG <g transform="..."> elements.
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

        $width = isset($data['width']) ? (int) $data['width'] : 0;
        $height = isset($data['height']) ? (int) $data['height'] : 0;

        $body = SvgParser::sanitizeContent((string) $data['body']);

        $hasTransforms = false;

        if ($width > 0 && $height > 0) {
            [$body, $width, $height, $hasTransforms] = self::applyIconifyTransforms($body, $data, $width, $height);
        }

        $attributes = [];

        if ($width > 0 && $height > 0) {
            // Reset viewBox origin to 0 0 when transforms have repositioned the content
            $left = $hasTransforms ? 0 : (int) ($data['left'] ?? 0);
            $top = $hasTransforms ? 0 : (int) ($data['top'] ?? 0);

            $attributes['viewBox'] = "{$left} {$top} {$width} {$height}";
            $attributes['width'] = (string) $width;
            $attributes['height'] = (string) $height;
        }

        return new self($body, $attributes);
    }

    /**
     * Apply Iconify transform properties (hFlip, vFlip, rotate) to SVG body content.
     *
     * Rotation is applied first (inner <g>), then flips (outer <g>), so flips operate
     * in the post-rotation coordinate space. Dimensions are swapped for 90°/270° rotations.
     *
     * @param string $body SVG inner content
     * @param array<string, mixed> $data Iconify icon data
     * @param int $width Icon width
     * @param int $height Icon height
     *
     * @return array{0: string, 1: int, 2: int, 3: bool} [body, width, height, transformed]
     */
    private static function applyIconifyTransforms(string $body, array $data, int $width, int $height): array
    {
        $rotate = (int) ($data['rotate'] ?? 0) % 4;
        $hFlip = (bool) ($data['hFlip'] ?? false);
        $vFlip = (bool) ($data['vFlip'] ?? false);

        if ($rotate === 0 && !$hFlip && !$vFlip) {
            return [$body, $width, $height, false];
        }

        // Apply rotation (inner <g> — rendered first against the body)
        // Transform math: rotate(deg) in SVG rotates CW; translate repositions into positive space.
        //   rotate=1 (90° CW):  (x,y) → (H−y, x)  via translate(H,0) rotate(90)
        //   rotate=2 (180°):    (x,y) → (W−x, H−y) via translate(W,H) rotate(180)
        //   rotate=3 (270° CW): (x,y) → (y, W−x)   via translate(0,W) rotate(270)
        if ($rotate !== 0) {
            $rotTransform = match ($rotate) {
                1 => "translate({$height}, 0) rotate(90)",
                2 => "translate({$width}, {$height}) rotate(180)",
                3 => "translate(0, {$width}) rotate(270)",
                default => '',
            };

            if ($rotTransform !== '') {
                $body = "<g transform=\"{$rotTransform}\">{$body}</g>";
            }

            // 90° and 270° rotations swap width ↔ height
            if ($rotate === 1 || $rotate === 3) {
                [$width, $height] = [$height, $width];
            }
        }

        // Apply flip (outer <g> — operates in post-rotation coordinate space)
        if ($hFlip || $vFlip) {
            $flipTransform = match (true) {
                $hFlip && $vFlip => "translate({$width}, {$height}) scale(-1, -1)",
                $hFlip => "translate({$width}, 0) scale(-1, 1)",
                default => "translate(0, {$height}) scale(1, -1)",
            };

            $body = "<g transform=\"{$flipTransform}\">{$body}</g>";
        }

        return [$body, $width, $height, true];
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
     * Set rotation via inline CSS transform (returns new instance).
     *
     * @param float|int $degrees Rotation angle in degrees (clockwise)
     */
    public function rotate(int|float $degrees): self
    {
        $value = "rotate({$degrees}deg)";
        $existing = $this->getAttribute('style') ?? '';

        $style = $existing !== ''
            ? rtrim($existing, '; ') . '; transform: ' . $value
            : 'transform: ' . $value;

        return $this->attr(['style' => $style]);
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
        $this->content = \is_string($data['content'] ?? null) ? $data['content'] : '';
        $this->attributes = \is_array($data['attributes'] ?? null) ? $data['attributes'] : [];
    }
}
