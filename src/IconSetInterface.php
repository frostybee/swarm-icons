<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

/**
 * Contract for third-party icon set packages.
 *
 * Implement this interface in external Composer packages to enable
 * auto-discovery via PackageDiscovery. Bundled icon sets use
 * SwarmIconsConfig::discoverJsonSets() instead.
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
     * Register this icon set with the given IconManager.
     */
    public static function register(IconManager $manager): void;
}
