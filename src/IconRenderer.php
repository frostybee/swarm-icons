<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

/**
 * Renders icons with attribute merging and accessibility features.
 *
 * Handles attribute merge order and automatic ARIA attributes.
 */
class IconRenderer
{
    /**
     * @param array<string, string> $defaultAttributes Global default attributes
     * @param array<string, array<string, string>> $prefixAttributes Prefix-specific default attributes
     */
    public function __construct(
        private array $defaultAttributes = [],
        private array $prefixAttributes = []
    ) {
    }

    /**
     * Render an icon with merged attributes.
     *
     * Merge order: icon defaults → global defaults → prefix defaults → caller attributes
     * CSS classes are merged (not replaced).
     * Automatic ARIA: decorative icons get aria-hidden="true", labeled icons get role="img".
     *
     * @param Icon $icon Base icon instance
     * @param string|null $prefix Icon prefix (for prefix-specific attributes)
     * @param array<string, string|int|float|bool|null> $attributes Caller-provided attributes
     * @return Icon Rendered icon with merged attributes
     */
    public function render(Icon $icon, ?string $prefix = null, array $attributes = []): Icon
    {
        // Start with icon's existing attributes
        $merged = $icon->getAttributes();

        // Merge global defaults
        $merged = $this->mergeAttributes($merged, $this->defaultAttributes);

        // Merge prefix-specific defaults
        if ($prefix !== null && isset($this->prefixAttributes[$prefix])) {
            $merged = $this->mergeAttributes($merged, $this->prefixAttributes[$prefix]);
        }

        // Merge caller attributes
        $merged = $this->mergeAttributes($merged, $attributes);

        // Apply ARIA accessibility rules
        $merged = $this->applyAriaRules($merged);

        // Create new icon with merged attributes (overwrite, not merge)
        return $icon->attr($merged, merge: false);
    }

    /**
     * Merge attributes with special handling for CSS classes.
     *
     * @param array<string, string> $base Base attributes
     * @param array<string, string|int|float|bool|null> $override Overriding attributes
     * @return array<string, string>
     */
    private function mergeAttributes(array $base, array $override): array
    {
        $result = $base;

        foreach ($override as $key => $value) {
            // Skip null values
            if ($value === null) {
                continue;
            }

            // Special handling for CSS classes: merge instead of replace
            if ($key === 'class') {
                $existing = $result['class'] ?? '';
                $new = $this->normalizeAttributeValue($value);
                $result['class'] = trim($existing . ' ' . $new);
            } else {
                $result[$key] = $this->normalizeAttributeValue($value);
            }
        }

        return $result;
    }

    /**
     * Apply automatic ARIA accessibility rules.
     *
     * - If icon has aria-label or aria-labelledby → add role="img"
     * - Otherwise → add aria-hidden="true" (decorative)
     *
     * @param array<string, string> $attributes
     * @return array<string, string>
     */
    private function applyAriaRules(array $attributes): array
    {
        $hasLabel = isset($attributes['aria-label']) || isset($attributes['aria-labelledby']);

        if ($hasLabel) {
            // Icon is labeled → make it accessible
            if (!isset($attributes['role'])) {
                $attributes['role'] = 'img';
            }
        } else {
            // Icon is decorative → hide from screen readers
            if (!isset($attributes['aria-hidden'])) {
                $attributes['aria-hidden'] = 'true';
            }
        }

        return $attributes;
    }

    /**
     * Normalize attribute value to string.
     *
     * @param string|int|float|bool $value
     * @return string
     */
    private function normalizeAttributeValue(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Set global default attributes.
     *
     * @param array<string, string> $attributes
     * @return void
     */
    public function setDefaultAttributes(array $attributes): void
    {
        $this->defaultAttributes = $attributes;
    }

    /**
     * Get global default attributes.
     *
     * @return array<string, string>
     */
    public function getDefaultAttributes(): array
    {
        return $this->defaultAttributes;
    }

    /**
     * Set prefix-specific default attributes.
     *
     * @param string $prefix
     * @param array<string, string> $attributes
     * @return void
     */
    public function setPrefixAttributes(string $prefix, array $attributes): void
    {
        $this->prefixAttributes[$prefix] = $attributes;
    }

    /**
     * Get prefix-specific default attributes.
     *
     * @param string $prefix
     * @return array<string, string>
     */
    public function getPrefixAttributes(string $prefix): array
    {
        return $this->prefixAttributes[$prefix] ?? [];
    }

    /**
     * Get all prefix attributes.
     *
     * @return array<string, array<string, string>>
     */
    public function getAllPrefixAttributes(): array
    {
        return $this->prefixAttributes;
    }
}
