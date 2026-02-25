<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Util;

/**
 * Downloads packages from the npm registry without requiring Node.js.
 *
 * Speaks directly to the npm registry HTTP API to fetch metadata,
 * download tarballs, and extract files from .tgz archives.
 *
 * Requires ext-zlib for gzip decompression.
 */
class NpmDownloader
{
    private const USER_AGENT = 'swarm-icons-build/1.0';
    private const REGISTRY_URL = 'https://registry.npmjs.org';
    private const ICONIFY_API_URL = 'https://api.iconify.design';

    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/swarm-icons-build';
    }

    /**
     * Fetch the latest version of an npm package from the registry.
     */
    public function fetchLatestVersion(string $package): ?string
    {
        $meta = $this->fetchVersionMetadata($package);

        return $meta['version'] ?? null;
    }

    /**
     * Fetch version metadata for the latest release of an npm package.
     *
     * @return array{version: string, unpackedSize: int|null}|null
     */
    public function fetchVersionMetadata(string $package): ?array
    {
        $encodedPackage = str_replace('/', '%2F', $package);
        $url = self::REGISTRY_URL . "/{$encodedPackage}/latest";

        $json = $this->httpGet($url, 30);
        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);
        if (!\is_array($data) || !isset($data['version'])) {
            return null;
        }

        $unpackedSize = $data['dist']['unpackedSize'] ?? null;

        return [
            'version' => $data['version'],
            'unpackedSize' => \is_int($unpackedSize) ? $unpackedSize : null,
        ];
    }

    /**
     * Fetch all available Iconify icon set collections.
     *
     * Calls the Iconify /collections API and returns metadata for every set.
     *
     * @return array<string, array{name: string, total: int, author: string, license: string, category: string}>|null
     */
    public function fetchCollections(): ?array
    {
        $json = $this->httpGet(self::ICONIFY_API_URL . '/collections', 30);
        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);
        if (!\is_array($data)) {
            return null;
        }

        $collections = [];
        foreach ($data as $prefix => $meta) {
            if (!\is_string($prefix) || !\is_array($meta)) {
                continue;
            }

            $collections[$prefix] = [
                'name' => (string) ($meta['name'] ?? $prefix),
                'total' => (int) ($meta['total'] ?? 0),
                'author' => (string) ($meta['author']['name'] ?? 'Unknown'),
                'license' => (string) ($meta['license']['title'] ?? 'Unknown'),
                'category' => (string) ($meta['category'] ?? ''),
            ];
        }

        return $collections;
    }

    /**
     * Download the npm tarball for a package@version and return a local temp file path.
     *
     * Downloads are cached in the temp directory — re-runs skip the download.
     */
    public function downloadTarball(string $package, string $version): ?string
    {
        $safeName = preg_replace('/[^a-z0-9\-]/', '-', strtolower($package)) . '-' . $version;
        $tarball = $this->cacheDir . '/' . $safeName . '.tgz';

        if (file_exists($tarball)) {
            return $tarball;
        }

        @mkdir(\dirname($tarball), 0o755, true);

        // Fetch tarball URL from registry metadata
        $encodedPackage = str_replace('/', '%2F', $package);
        $metaUrl = self::REGISTRY_URL . "/{$encodedPackage}/{$version}";

        $json = $this->httpGet($metaUrl, 30);
        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);
        if (!\is_array($data)) {
            return null;
        }

        $tarballUrl = $data['dist']['tarball'] ?? null;
        if (!\is_string($tarballUrl)) {
            return null;
        }

        $bytes = $this->httpGet($tarballUrl, 120);
        if ($bytes === null) {
            return null;
        }

        file_put_contents($tarball, $bytes);

        return $tarball;
    }

    /**
     * Extract files matching a glob pattern from a .tgz archive.
     *
     * @return array<string, string> Map of filename => content
     */
    public function extractByGlob(string $tarballPath, string $globPattern): array
    {
        $tarContent = $this->decompressTarball($tarballPath);
        if ($tarContent === null) {
            return [];
        }

        return $this->parseTarByGlob($tarContent, $globPattern);
    }

    /**
     * Extract a single named file from a .tgz archive.
     *
     * The file path is relative to the tarball root (the leading "package/"
     * prefix added by npm is stripped automatically).
     *
     * @param string $tarballPath Path to the .tgz file
     * @param string $filePath Relative path inside the archive (e.g., "icons.json")
     *
     * @return string|null File contents, or null if not found
     */
    public function extractFile(string $tarballPath, string $filePath): ?string
    {
        $tarContent = $this->decompressTarball($tarballPath);
        if ($tarContent === null) {
            return null;
        }

        return $this->parseTarForFile($tarContent, $filePath);
    }

    /**
     * Decompress a .tgz file into raw TAR content.
     */
    private function decompressTarball(string $tarballPath): ?string
    {
        if (!\extension_loaded('zlib')) {
            return null;
        }

        $gz = gzopen($tarballPath, 'rb');
        if ($gz === false) {
            return null;
        }

        $tarContent = '';
        while (!gzeof($gz)) {
            $tarContent .= gzread($gz, 65536);
        }
        gzclose($gz);

        return $tarContent;
    }

    /**
     * Parse raw TAR content and extract files matching a glob-like pattern.
     *
     * @return array<string, string> Map of basename => content
     */
    private function parseTarByGlob(string $tarContent, string $globPattern): array
    {
        // Convert glob to regex: *.svg → [^/]*\.svg
        $regex = '#' . str_replace(
            ['*', '?', '.'],
            ['[^/]*', '[^/]', '\\.'],
            $globPattern,
        ) . '$#i';

        $files = [];
        $offset = 0;
        $length = \strlen($tarContent);

        while ($offset + 512 <= $length) {
            $header = substr($tarContent, $offset, 512);

            if (trim($header) === '') {
                break;
            }

            $nameRaw = rtrim(substr($header, 0, 100), "\0");
            $sizeOctal = trim(substr($header, 124, 12));
            $typeFlag = $header[156];

            $size = (int) octdec($sizeOctal);

            $offset += 512;

            if ($typeFlag === '0' || $typeFlag === "\0") {
                $name = preg_replace('#^[^/]+/#', '', $nameRaw) ?? $nameRaw;

                if (preg_match($regex, $name) && $size > 0) {
                    $content = substr($tarContent, $offset, $size);
                    $files[basename($name)] = $content;
                }
            }

            $offset += (int) (ceil($size / 512) * 512);
        }

        return $files;
    }

    /**
     * Parse raw TAR content and extract a single file by path.
     */
    private function parseTarForFile(string $tarContent, string $filePath): ?string
    {
        $offset = 0;
        $length = \strlen($tarContent);

        while ($offset + 512 <= $length) {
            $header = substr($tarContent, $offset, 512);

            if (trim($header) === '') {
                break;
            }

            $nameRaw = rtrim(substr($header, 0, 100), "\0");
            $sizeOctal = trim(substr($header, 124, 12));
            $typeFlag = $header[156];

            $size = (int) octdec($sizeOctal);

            $offset += 512;

            if ($typeFlag === '0' || $typeFlag === "\0") {
                // Strip leading "package/" that npm adds to all paths
                $name = preg_replace('#^[^/]+/#', '', $nameRaw) ?? $nameRaw;

                if ($name === $filePath && $size > 0) {
                    return substr($tarContent, $offset, $size);
                }
            }

            $offset += (int) (ceil($size / 512) * 512);
        }

        return null;
    }

    /**
     * Perform an HTTP GET request.
     */
    private function httpGet(string $url, int $timeout): ?string
    {
        $ctx = stream_context_create(['http' => [
            'timeout' => $timeout,
            'header' => 'User-Agent: ' . self::USER_AGENT . "\r\n",
        ]]);

        $result = @file_get_contents($url, false, $ctx);

        return $result === false ? null : $result;
    }
}
