<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Bootstrap;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconSetInterface;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;

/**
 * Registers Bootstrap Icons (~2,000 SVGs) with the SwarmIcons manager.
 *
 * Usage:
 * ```php
 * BootstrapIconSet::register($manager);
 * echo icon('bi:house');
 * ```
 *
 * SVGs are located in resources/svg/. Populate with:
 * ```
 * php bin/build-icon-sets.php bootstrap
 * ```
 */
class BootstrapIconSet implements IconSetInterface
{
    public static function prefix(): string
    {
        return 'bi';
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
