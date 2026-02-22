<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use Frostybee\SwarmIcons\Exception\SwarmIconsException;

/**
 * Static accessor for the global IconManager instance.
 *
 * Provides a convenient way to access icons via the global icon() helper function.
 */
class SwarmIcons
{
    private static ?IconManager $manager = null;

    /**
     * Set the global icon manager instance.
     */
    public static function setManager(IconManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * Get the global icon manager instance.
     *
     * @throws SwarmIconsException
     */
    public static function getManager(): IconManager
    {
        if (self::$manager === null) {
            throw new SwarmIconsException(
                'No IconManager instance set. Call SwarmIcons::setManager() first.',
            );
        }

        return self::$manager;
    }

    /**
     * Check if a manager has been set.
     */
    public static function hasManager(): bool
    {
        return self::$manager !== null;
    }

    /**
     * Get an icon by name.
     *
     * @param string $name Icon name (with or without prefix)
     * @param array<string, bool|float|int|string|null> $attributes Additional attributes
     *
     * @throws Exception\IconNotFoundException
     * @throws Exception\InvalidIconNameException
     * @throws SwarmIconsException
     *
     * @return Icon Rendered icon
     */
    public static function get(string $name, array $attributes = []): Icon
    {
        return self::getManager()->get($name, $attributes);
    }

    /**
     * Check if an icon exists.
     *
     * @param string $name Icon name (with or without prefix)
     *
     * @throws SwarmIconsException
     */
    public static function has(string $name): bool
    {
        return self::getManager()->has($name);
    }

    /**
     * Reset the global instance (mainly for testing).
     */
    public static function reset(): void
    {
        self::$manager = null;
    }
}
