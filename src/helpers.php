<?php

declare(strict_types=1);

use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\SwarmIcons;

if (!function_exists('icon')) {
    /**
     * Get an icon by name.
     *
     * This is a global helper function that delegates to the SwarmIcons static accessor.
     * You must call SwarmIcons::setManager() before using this function.
     *
     * @param string $name Icon name (with or without prefix, e.g., 'tabler:home' or 'home')
     * @param array<string, string|int|float|bool|null> $attributes Additional attributes
     * @return Icon Rendered icon
     * @throws \Frostybee\SwarmIcons\Exception\IconNotFoundException
     * @throws \Frostybee\SwarmIcons\Exception\InvalidIconNameException
     */
    function icon(string $name, array $attributes = []): Icon
    {
        return SwarmIcons::get($name, $attributes);
    }
}
