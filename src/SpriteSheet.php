<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use Stringable;

/**
 * Collects icons into an SVG sprite sheet with <symbol> definitions.
 *
 * Instead of inlining each icon's full SVG, icons are registered as <symbol>
 * elements in a hidden sprite sheet and referenced via <use href="#id"/>.
 * This reduces DOM size when the same icon appears multiple times on a page.
 */
class SpriteSheet implements Stringable
{
    /** @var array<string, Icon> Symbol ID → Icon */
    private array $symbols = [];

    public function __construct(
        private IconManager $manager,
    ) {}

    /**
     * Register an icon and return a <svg><use> reference.
     *
     * The icon is added to the sprite sheet on first use. Subsequent calls
     * with the same name return a reference without re-registering.
     *
     * @param string $name Icon name (e.g., 'tabler:home')
     * @param array<string, bool|float|int|string|null> $attributes Attributes for the <svg> wrapper
     *
     * @return string SVG markup with <use href="#id"/>
     */
    public function use(string $name, array $attributes = []): string
    {
        $id = $this->nameToId($name);

        if (!isset($this->symbols[$id])) {
            $this->symbols[$id] = $this->manager->get($name);
        }

        $attributesString = $this->renderAttributes($attributes);

        return "<svg{$attributesString}><use href=\"#{$id}\"/></svg>";
    }

    /**
     * Check if an icon has been registered in the sprite sheet.
     */
    public function has(string $name): bool
    {
        return isset($this->symbols[$this->nameToId($name)]);
    }

    /**
     * Get the number of registered symbols.
     */
    public function count(): int
    {
        return \count($this->symbols);
    }

    /**
     * Render the hidden sprite sheet containing all <symbol> definitions.
     *
     * Place this once in your HTML (typically at the top of <body>).
     */
    public function render(): string
    {
        if ($this->symbols === []) {
            return '';
        }

        $symbols = '';
        foreach ($this->symbols as $id => $icon) {
            $viewBox = $icon->getAttribute('viewBox') ?? '0 0 24 24';
            $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
            $safeViewBox = htmlspecialchars($viewBox, ENT_QUOTES, 'UTF-8');
            $symbols .= "<symbol id=\"{$safeId}\" viewBox=\"{$safeViewBox}\">{$icon->getContent()}</symbol>";
        }

        return "<svg xmlns=\"http://www.w3.org/2000/svg\" style=\"display:none\">{$symbols}</svg>";
    }

    /**
     * Reset the sprite sheet (clear all registered symbols).
     */
    public function reset(): void
    {
        $this->symbols = [];
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Convert an icon name to a valid HTML ID.
     */
    private function nameToId(string $name): string
    {
        return str_replace([':', '/'], '-', $name);
    }

    /**
     * Render attributes as a string.
     *
     * @param array<string, bool|float|int|string|null> $attributes
     */
    private function renderAttributes(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $parts = [];
        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (!\is_string($key) || !preg_match('/^[a-zA-Z_:][\w:.\-]*$/', $key)) {
                continue;
            }

            $stringValue = \is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            $escapedValue = htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8');
            $parts[] = "{$key}=\"{$escapedValue}\"";
        }

        return $parts !== [] ? ' ' . implode(' ', $parts) : '';
    }
}
