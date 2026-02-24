<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Twig;

use Frostybee\SwarmIcons\Exception\IconNotFoundException;
use Frostybee\SwarmIcons\Exception\InvalidIconNameException;
use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\IconManager;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Twig runtime for rendering icons.
 *
 * Handles the actual icon() function execution in Twig templates.
 */
class SwarmIconsRuntime implements RuntimeExtensionInterface
{
    /**
     * @param IconManager $manager Icon manager instance
     * @param bool $silentOnMissing Return HTML comment instead of throwing on missing icons
     */
    public function __construct(
        private readonly IconManager $manager,
        private readonly bool $silentOnMissing = false,
    ) {}

    /**
     * Render an icon in a Twig template.
     *
     * @param string $name Icon name (with or without prefix)
     * @param array<string, bool|float|int|string|null> $attributes Additional attributes
     *
     * @return string Rendered SVG HTML
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        try {
            $icon = $this->manager->get($name, $attributes);
            return $icon->toHtml();
        } catch (IconNotFoundException | InvalidIconNameException $e) {
            if ($this->silentOnMissing) {
                return $this->renderMissingIconComment($name, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Check if an icon exists.
     *
     * Useful for conditional rendering in templates.
     *
     * @param string $name Icon name
     */
    public function hasIcon(string $name): bool
    {
        return $this->manager->has($name);
    }

    /**
     * Get icon as an Icon object (for advanced manipulation in templates).
     *
     * @param string $name Icon name
     * @param array<string, bool|float|int|string|null> $attributes Additional attributes
     *
     * @return Icon|null Icon instance or null if not found
     */
    public function getIcon(string $name, array $attributes = []): ?Icon
    {
        try {
            return $this->manager->get($name, $attributes);
        } catch (IconNotFoundException | InvalidIconNameException) {
            return null;
        }
    }

    /**
     * Render an HTML comment for a missing icon.
     *
     * @param string $name Icon name
     * @param string $error Error message
     *
     * @return string HTML comment
     */
    private function renderMissingIconComment(string $name, string $error): string
    {
        $safeName = str_replace('--', '- -', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'));
        $safeError = str_replace('--', '- -', htmlspecialchars($error, ENT_QUOTES, 'UTF-8'));

        return "<!-- SwarmIcons: Icon '{$safeName}' not found ({$safeError}) -->";
    }
}
