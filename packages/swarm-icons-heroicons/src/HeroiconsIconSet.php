<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Heroicons;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconSetInterface;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;

/**
 * Registers Heroicons (~300 SVGs) with the SwarmIcons manager.
 *
 * Usage:
 * ```php
 * HeroiconsIconSet::register($manager);
 * echo icon('heroicons:home');
 * ```
 *
 * SVGs are located in resources/svg/ (organized in outline/ and solid/ subdirs).
 * Populate with:
 * ```
 * php bin/build-icon-sets.php heroicons
 * ```
 */
class HeroiconsIconSet implements IconSetInterface
{
    public static function prefix(): string
    {
        return 'heroicons';
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
