<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Exception;

/**
 * Thrown when an icon name format is invalid.
 */
class InvalidIconNameException extends SwarmIconsException
{
    public static function forName(string $name): self
    {
        return new self("Invalid icon name format: {$name}");
    }
}
