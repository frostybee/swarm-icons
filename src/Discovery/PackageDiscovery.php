<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Discovery;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconSetInterface;

/**
 * Discovers and registers icon set packages installed via Composer.
 *
 * Icon set packages declare themselves in their composer.json:
 *
 * ```json
 * "extra": {
 *     "swarm-icons": {
 *         "prefix": "tabler",
 *         "provider-class": "Frostybee\\SwarmIcons\\Tabler\\TablerIconSet"
 *     }
 * }
 * ```
 *
 * Usage:
 * ```php
 * PackageDiscovery::registerAll($manager, dirname(__DIR__, 2) . '/vendor');
 * ```
 */
class PackageDiscovery
{
    /**
     * Scan installed Composer packages and return metadata for all swarm-icons sets.
     *
     * @param string $vendorPath Absolute path to the Composer vendor directory
     *
     * @return array<int, array{prefix: string, provider-class: string, package: string}>
     */
    public static function discover(string $vendorPath): array
    {
        $installedJson = $vendorPath . '/composer/installed.json';

        if (!is_file($installedJson)) {
            return [];
        }

        $json = file_get_contents($installedJson);
        if ($json === false) {
            return [];
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!\is_array($data)) {
            return [];
        }

        // Composer >= 2.0 wraps packages under a "packages" key
        $packages = $data['packages'] ?? $data;
        if (!\is_array($packages)) {
            return [];
        }

        $discovered = [];

        foreach ($packages as $package) {
            if (!\is_array($package)) {
                continue;
            }

            $extra = $package['extra'] ?? [];
            if (!isset($extra['swarm-icons']) || !\is_array($extra['swarm-icons'])) {
                continue;
            }

            $meta = $extra['swarm-icons'];
            $prefix = $meta['prefix'] ?? null;
            $providerClass = $meta['provider-class'] ?? null;

            if (!\is_string($prefix) || $prefix === '' || !\is_string($providerClass) || $providerClass === '') {
                continue;
            }

            $discovered[] = [
                'prefix' => $prefix,
                'provider-class' => $providerClass,
                'package' => $package['name'] ?? 'unknown',
            ];
        }

        return $discovered;
    }

    /**
     * Discover and register all icon set packages with the given manager.
     *
     * @param string $vendorPath Absolute path to the Composer vendor directory
     */
    public static function registerAll(IconManager $manager, string $vendorPath): void
    {
        foreach (self::discover($vendorPath) as $meta) {
            $class = $meta['provider-class'];

            if (!class_exists($class)) {
                continue;
            }

            if (!is_a($class, IconSetInterface::class, true)) {
                continue;
            }

            /** @var class-string<IconSetInterface> $class */
            $class::register($manager);
        }
    }
}
