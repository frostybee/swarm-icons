<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Phosphor;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconSetInterface;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;

/**
 * Registers Phosphor Icons (~7,000 SVGs) with the SwarmIcons manager.
 *
 * Usage:
 * ```php
 * PhosphorIconSet::register($manager);
 * echo icon('phosphor:house');
 * ```
 *
 * SVGs are organized by weight (bold/, duotone/, fill/, light/, regular/, thin/).
 * Example: icon('phosphor:bold/house') or icon('phosphor:house') for regular.
 *
 * Populate with:
 * ```
 * php bin/build-icon-sets.php phosphor
 * ```
 */
class PhosphorIconSet implements IconSetInterface
{
    public static function prefix(): string
    {
        return 'phosphor';
    }

    public static function directory(): string
    {
        return dirname(__DIR__) . '/resources/svg';
    }

    /**
     * @return array<string, string>
     */
    public static function defaultAttributes(): array
    {
        return [
            'fill' => 'currentColor',
        ];
    }

    public static function register(IconManager $manager): void
    {
        $provider = new DirectoryProvider(self::directory());
        $manager->register(self::prefix(), $provider);
    }
}
