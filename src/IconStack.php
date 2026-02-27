<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use Stringable;

/**
 * Layers multiple icons into a single composite SVG.
 *
 * Each icon is rendered as a <g> layer within a shared <svg> container,
 * similar to Font Awesome's fa-stack. The viewBox is taken from the first icon.
 */
class IconStack implements Stringable
{
    /** @var array<int, array{icon: Icon, attributes: array<string, string>}> */
    private array $layers = [];

    /** @var array<string, string> Container SVG attributes */
    private array $attributes = [];

    /**
     * Add an icon layer (returns new instance).
     *
     * @param Icon $icon Icon to add as a layer
     * @param array<string, string> $attributes Attributes for this layer's <g> element
     */
    public function push(Icon $icon, array $attributes = []): self
    {
        $clone = clone $this;
        $clone->layers[] = [
            'icon' => $icon,
            'attributes' => $attributes,
        ];

        return $clone;
    }

    /**
     * Set container SVG attributes (returns new instance).
     *
     * @param array<string, string> $attributes
     */
    public function attr(array $attributes): self
    {
        $clone = clone $this;
        $clone->attributes = array_merge($clone->attributes, $attributes);

        return $clone;
    }

    /**
     * Set width and height on the container (returns new instance).
     */
    public function size(string|int $size): self
    {
        $sizeStr = (string) $size;

        return $this->attr(['width' => $sizeStr, 'height' => $sizeStr]);
    }

    /**
     * Add CSS classes to the container (returns new instance).
     *
     * @param array<int, string>|string $classes
     */
    public function class(string|array $classes): self
    {
        $classString = \is_array($classes) ? implode(' ', $classes) : $classes;
        $existing = $this->attributes['class'] ?? '';
        $merged = trim($existing . ' ' . $classString);

        return $this->attr(['class' => $merged]);
    }

    /**
     * Get the number of layers.
     */
    public function count(): int
    {
        return \count($this->layers);
    }

    /**
     * Render the stacked icons as a single SVG.
     */
    public function toHtml(): string
    {
        if ($this->layers === []) {
            return '<svg></svg>';
        }

        // Use viewBox from the first layer
        $viewBox = $this->layers[0]['icon']->getAttribute('viewBox') ?? '0 0 24 24';

        $layersHtml = '';
        foreach ($this->layers as $layer) {
            $attrs = $this->renderLayerAttributes($layer['attributes']);
            $layersHtml .= "<g{$attrs}>{$layer['icon']->getContent()}</g>";
        }

        $containerAttrs = $this->renderContainerAttributes($viewBox);

        return "<svg{$containerAttrs}>{$layersHtml}</svg>";
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * Render layer <g> attributes.
     *
     * @param array<string, string> $attributes
     */
    private function renderLayerAttributes(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $parts = [];
        foreach ($attributes as $name => $value) {
            if (!preg_match('/^[a-zA-Z_:][\w:.\-]*$/', $name)) {
                continue;
            }
            $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $parts[] = "{$name}=\"{$escapedValue}\"";
        }

        return $parts !== [] ? ' ' . implode(' ', $parts) : '';
    }

    /**
     * Render container <svg> attributes including viewBox.
     */
    private function renderContainerAttributes(string $viewBox): string
    {
        $attrs = array_merge(['viewBox' => $viewBox], $this->attributes);

        $parts = [];
        foreach ($attrs as $name => $value) {
            if (!preg_match('/^[a-zA-Z_:][\w:.\-]*$/', $name)) {
                continue;
            }
            $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $parts[] = "{$name}=\"{$escapedValue}\"";
        }

        return ' ' . implode(' ', $parts);
    }
}
