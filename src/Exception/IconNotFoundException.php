<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Exception;

/**
 * Thrown when a requested icon cannot be found.
 */
class IconNotFoundException extends SwarmIconsException
{
    public static function forName(string $name): self
    {
        return new self("Icon not found: {$name}");
    }
}
