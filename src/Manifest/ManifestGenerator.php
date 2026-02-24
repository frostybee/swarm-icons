<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Manifest;

use Frostybee\SwarmIcons\IconManager;
use RuntimeException;

/**
 * Generates a JSON manifest of all available icon names.
 *
 * Useful for IDE autocompletion, tooling, and icon pickers.
 */
class ManifestGenerator
{
    /**
     * Generate a manifest from an IconManager.
     *
     * @param IconManager $manager Configured icon manager
     * @param array<string>|null $prefixes Specific prefixes to include (null = all)
     *
     * @return array<string, array<string>> Map of prefix => icon names
     */
    public function generate(IconManager $manager, ?array $prefixes = null): array
    {
        $targetPrefixes = $prefixes ?? $manager->getRegisteredPrefixes();
        $manifest = [];

        foreach ($targetPrefixes as $prefix) {
            $icons = iterator_to_array($manager->all($prefix));
            sort($icons);
            $manifest[$prefix] = $icons;
        }

        return $manifest;
    }

    /**
     * Generate a manifest and return it as JSON.
     *
     * @param IconManager $manager Configured icon manager
     * @param array<string>|null $prefixes Specific prefixes to include (null = all)
     *
     * @return string JSON string
     */
    public function toJson(IconManager $manager, ?array $prefixes = null): string
    {
        $manifest = $this->generate($manager, $prefixes);

        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Generate a manifest and write it to a file.
     *
     * @param IconManager $manager Configured icon manager
     * @param string $outputPath File path to write to
     * @param array<string>|null $prefixes Specific prefixes to include (null = all)
     *
     * @return array{prefixes: int, icons: int} Count summary
     */
    public function toFile(IconManager $manager, string $outputPath, ?array $prefixes = null): array
    {
        $manifest = $this->generate($manager, $prefixes);

        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $dir = \dirname($outputPath);
        if (!is_dir($dir) && !@mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: {$dir}");
        }

        if (file_put_contents($outputPath, $json) === false) {
            throw new RuntimeException("Failed to write manifest to: {$outputPath}");
        }

        $totalIcons = 0;
        foreach ($manifest as $icons) {
            $totalIcons += \count($icons);
        }

        return [
            'prefixes' => \count($manifest),
            'icons' => $totalIcons,
        ];
    }
}
