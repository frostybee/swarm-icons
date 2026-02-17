<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Provider;

use Frostybee\SwarmIcons\Icon;

/**
 * Contract for icon providers.
 *
 * Providers are responsible for resolving icon names to Icon instances.
 */
interface IconProviderInterface
{
    /**
     * Get an icon by name.
     *
     * @param string $name Icon name (without prefix)
     * @return Icon|null Icon instance or null if not found
     */
    public function get(string $name): ?Icon;

    /**
     * Check if an icon exists.
     *
     * @param string $name Icon name (without prefix)
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get all available icon names.
     *
     * @return iterable<string> Icon names
     */
    public function all(): iterable;
}
