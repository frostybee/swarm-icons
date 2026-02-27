<?php

declare(strict_types=1);

use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\SwarmIcons;

if (!function_exists('swarm_icon')) {
    /**
     * Get an icon by name.
     *
     * This is a global helper function that delegates to the SwarmIcons static accessor.
     * You must call SwarmIcons::setManager() before using this function.
     *
     * @param string $name Icon name (with or without prefix, e.g., 'tabler:home' or 'home')
     * @param array<string, bool|float|int|string|null> $attributes Additional attributes
     *
     * @throws \Frostybee\SwarmIcons\Exception\IconNotFoundException
     * @throws \Frostybee\SwarmIcons\Exception\InvalidIconNameException
     *
     * @return Icon Rendered icon
     */
    function swarm_icon(string $name, array $attributes = []): Icon
    {
        return SwarmIcons::get($name, $attributes);
    }
}

if (!function_exists('sicon')) {
    /**
     * Shorthand alias for swarm_icon().
     *
     * @param string $name Icon name (with or without prefix, e.g., 'tabler:home' or 'home')
     * @param array<string, bool|float|int|string|null> $attributes Additional attributes
     *
     * @throws \Frostybee\SwarmIcons\Exception\IconNotFoundException
     * @throws \Frostybee\SwarmIcons\Exception\InvalidIconNameException
     *
     * @return Icon Rendered icon
     */
    function sicon(string $name, array $attributes = []): Icon
    {
        return swarm_icon($name, $attributes);
    }
}
