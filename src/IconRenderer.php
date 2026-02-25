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
    /** @var array<string, array<string, array<string, string>>> Suffix-based attributes (prefix → suffix → attributes) */
    private array $suffixAttributes = [];

    /**
     * @param array<string, string> $defaultAttributes Global default attributes
     * @param array<string, array<string, string>> $prefixAttributes Prefix-specific default attributes
     */
    public function __construct(
        private array $defaultAttributes = [],
        private array $prefixAttributes = [],
    ) {}

    /**
     * Render an icon with merged attributes.
     *
     * Merge order: icon defaults → global defaults → prefix defaults → caller attributes
     * CSS classes are merged (not replaced).
     * Automatic ARIA: decorative icons get aria-hidden="true", labeled icons get role="img".
     *
     * @param Icon $icon Base icon instance
     * @param string|null $prefix Icon prefix (for prefix-specific attributes)
     * @param array<string, bool|float|int|string|null> $attributes Caller-provided attributes
     * @param string $iconName Icon name without prefix (for suffix matching)
     *
     * @return Icon Rendered icon with merged attributes
     */
    public function render(Icon $icon, ?string $prefix = null, array $attributes = [], string $iconName = ''): Icon
    {
        // Start with icon's existing attributes
        $merged = $icon->getAttributes();

        // Merge global defaults
        $merged = $this->mergeAttributes($merged, $this->defaultAttributes);

        // Merge prefix-specific defaults
        if ($prefix !== null && isset($this->prefixAttributes[$prefix])) {
            $merged = $this->mergeAttributes($merged, $this->prefixAttributes[$prefix]);
        }

        // Merge suffix-specific defaults
        if ($prefix !== null && $iconName !== '') {
            $suffixAttrs = $this->resolveSuffixAttributes($prefix, $iconName);

            if ($suffixAttrs !== []) {
                $merged = $this->mergeAttributes($merged, $suffixAttrs);
            }
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
     * @param array<string, bool|float|int|string|null> $override Overriding attributes
     *
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
     *
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
     */
    private function normalizeAttributeValue(string|int|float|bool $value): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Set global default attributes.
     *
     * @param array<string, string> $attributes
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
     * @param array<string, string> $attributes
     */
    public function setPrefixAttributes(string $prefix, array $attributes): void
    {
        $this->prefixAttributes[$prefix] = $attributes;
    }

    /**
     * Get prefix-specific default attributes.
     *
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

    /**
     * Set suffix-specific default attributes for a prefix.
     *
     * @param string $prefix Icon set prefix
     * @param string $suffix Suffix to match (e.g., 'solid') or '' for fallback
     * @param array<string, string> $attributes
     */
    public function setSuffixAttributes(string $prefix, string $suffix, array $attributes): void
    {
        if (!isset($this->suffixAttributes[$prefix])) {
            $this->suffixAttributes[$prefix] = [];
        }

        $this->suffixAttributes[$prefix][$suffix] = $attributes;
    }

    /**
     * Get suffix attributes for a prefix.
     *
     * @return array<string, array<string, string>>
     */
    public function getSuffixAttributes(string $prefix): array
    {
        return $this->suffixAttributes[$prefix] ?? [];
    }

    /**
     * Resolve suffix-based attributes for an icon name.
     *
     * Checks non-empty suffixes first (longest match wins), then falls
     * back to the empty-string suffix rule if no match is found.
     *
     * @return array<string, string>
     */
    private function resolveSuffixAttributes(string $prefix, string $iconName): array
    {
        if (!isset($this->suffixAttributes[$prefix])) {
            return [];
        }

        $rules = $this->suffixAttributes[$prefix];

        // Check non-empty suffixes first
        foreach ($rules as $suffix => $attributes) {
            if ($suffix !== '' && str_ends_with($iconName, '-' . $suffix)) {
                return $attributes;
            }
        }

        // Fall back to empty-string suffix (default rule)
        if (isset($rules[''])) {
            return $rules[''];
        }

        return [];
    }
}
