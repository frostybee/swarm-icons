<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

/**
 * Contract for icon set packages (e.g., frostybee/swarm-icons-tabler).
 *
 * Each icon set package ships SVG files in resources/svg/ and provides a
 * registration class that implements this interface.
 */
interface IconSetInterface
{
    /**
     * The icon prefix used to reference icons from this set.
     *
     * Example: "tabler" → icon('tabler:home')
     */
    public static function prefix(): string;

    /**
     * Absolute path to the directory containing SVG files.
     */
    public static function directory(): string;

    /**
     * Default SVG attributes applied to all icons from this set.
     *
     * @return array<string, string>
     */
    public static function defaultAttributes(): array;

    /**
     * Register this icon set with the given IconManager.
     *
     * @param IconManager $manager
     */
    public static function register(IconManager $manager): void;
}
