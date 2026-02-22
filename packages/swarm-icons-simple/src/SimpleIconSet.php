<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Simple;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconSetInterface;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;

/**
 * Registers Simple Icons (~2,200 brand SVGs) with the SwarmIcons manager.
 *
 * Usage:
 * ```php
 * SimpleIconSet::register($manager);
 * echo icon('simple:github');
 * ```
 *
 * SVGs are located in resources/svg/. Populate with:
 * ```
 * php bin/build-icon-sets.php simple
 * ```
 */
class SimpleIconSet implements IconSetInterface
{
    public static function prefix(): string
    {
        return 'simple';
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
