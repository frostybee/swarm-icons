<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tabler;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconSetInterface;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;

/**
 * Registers Tabler Icons (~5,900 SVGs) with the SwarmIcons manager.
 *
 * Usage:
 * ```php
 * TablerIconSet::register($manager);
 * echo icon('tabler:home');
 * ```
 *
 * SVGs are located in resources/svg/ and can be populated with:
 * ```
 * php bin/build-icon-sets.php tabler
 * ```
 */
class TablerIconSet implements IconSetInterface
{
    public static function prefix(): string
    {
        return 'tabler';
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
            'stroke-width' => '1.5',
            'stroke' => 'currentColor',
            'fill' => 'none',
        ];
    }

    public static function register(IconManager $manager): void
    {
        $provider = new DirectoryProvider(self::directory());
        $manager->register(self::prefix(), $provider);
    }
}
