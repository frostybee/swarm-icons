<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Util;

/**
 * Manages the swarm-icons.json manifest that tracks downloaded JSON icon sets.
 *
 * The manifest format:
 * {
 *     "json-sets": ["mdi", "bi"],
 *     "versions": { "mdi": "7.4.47", "bi": "1.11.3" }
 * }
 *
 * The "versions" key is optional for backward compatibility with older manifests.
 */
class ManifestManager
{
    private string $manifestPath;

    public function __construct(?string $manifestPath = null)
    {
        $this->manifestPath = $manifestPath ?? getcwd() . '/swarm-icons.json';
    }

    /**
     * Load set prefixes from the manifest.
     *
     * @return list<string>|null Null if no manifest exists or is empty.
     */
    public function loadPrefixes(): ?array
    {
        $data = $this->readManifest();
        if ($data === null || !isset($data['json-sets']) || !\is_array($data['json-sets'])) {
            return null;
        }

        /** @var list<string> $sets */
        $sets = $data['json-sets'];

        return $sets !== [] ? array_values($sets) : null;
    }

    /**
     * Load version map from the manifest.
     *
     * @return array<string, string> prefix => version. Empty if no versions key.
     */
    public function loadVersions(): array
    {
        $data = $this->readManifest();
        if ($data === null || !isset($data['versions']) || !\is_array($data['versions'])) {
            return [];
        }

        /** @var array<string, string> */
        return $data['versions'];
    }

    /**
     * Save prefixes and versions to the manifest, merging with existing data.
     *
     * @param list<string> $prefixes
     * @param array<string, string> $versions prefix => version to merge
     */
    public function save(array $prefixes, array $versions = []): void
    {
        $existingPrefixes = $this->loadPrefixes() ?? [];
        $existingVersions = $this->loadVersions();

        $merged = array_values(array_unique(array_merge($existingPrefixes, $prefixes)));
        sort($merged);

        $mergedVersions = array_merge($existingVersions, $versions);
        ksort($mergedVersions);

        $data = ['json-sets' => $merged];
        if ($mergedVersions !== []) {
            $data['versions'] = $mergedVersions;
        }

        file_put_contents(
            $this->manifestPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    /**
     * Auto-detect the JSON resources directory.
     *
     * Resolves to resources/json/ relative to the core package root.
     * When installed as a dependency, checks the vendor path first.
     */
    public function resolveJsonDirectory(): ?string
    {
        // Core package: src/Util/ -> up 2 levels -> resources/json/
        $corePath = \dirname(__DIR__, 2) . '/resources/json';
        if (is_dir(\dirname($corePath))) {
            return $corePath;
        }

        // Installed as vendor dependency
        $vendorPath = \dirname(__DIR__, 3) . '/frostybee/swarm-icons/resources/json';
        if (is_dir(\dirname($vendorPath))) {
            return $vendorPath;
        }

        return null;
    }

    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }

    /**
     * Read and decode the manifest file.
     *
     * @return array<string, mixed>|null
     */
    private function readManifest(): ?array
    {
        if (!file_exists($this->manifestPath)) {
            return null;
        }

        $content = file_get_contents($this->manifestPath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return \is_array($data) ? $data : null;
    }
}
