#!/usr/bin/env php
<?php

/**
 * SwarmIcons — Icon Set Build Script
 *
 * Downloads SVG files from upstream npm packages and places them into
 * the corresponding packages/swarm-icons-*/resources/svg/ directories.
 *
 * Usage:
 *   php bin/build-icon-sets.php              # Build all icon sets
 *   php bin/build-icon-sets.php tabler       # Build a single set
 *   php bin/build-icon-sets.php tabler lucide heroicons
 *
 * Requirements: PHP 8.2+, ext-zlib (for gzip decompression)
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

/**
 * Icon set definitions.
 *
 * Each entry maps a set key to:
 *   - package:  npm package name
 *   - svg_path: glob pattern (relative to the extracted tarball root) matching SVG files
 *   - dest:     destination directory inside packages/swarm-icons-{key}/resources/svg/
 *               Use '' to copy files directly, or a subdir name to preserve structure.
 *   - flatten:  if true, all SVGs are placed flat in dest (strip subdirectory structure)
 */
$iconSets = [
    'tabler' => [
        'package'  => '@tabler/icons',
        'svg_path' => 'icons/outline/*.svg',
        'dest'     => '',
        'flatten'  => true,
    ],
    'heroicons' => [
        'package'  => 'heroicons',
        'svg_path' => 'optimized/24/outline/*.svg',
        'dest'     => 'outline',
        'flatten'  => true,
    ],
    'lucide' => [
        'package'  => 'lucide-static',
        'svg_path' => 'icons/*.svg',
        'dest'     => '',
        'flatten'  => true,
    ],
    'bootstrap' => [
        'package'  => 'bootstrap-icons',
        'svg_path' => 'icons/*.svg',
        'dest'     => '',
        'flatten'  => true,
    ],
    'phosphor' => [
        'package'  => '@phosphor-icons/core',
        'svg_path' => 'assets/regular/*.svg',
        'dest'     => '',
        'flatten'  => true,
    ],
    'simple' => [
        'package'  => 'simple-icons',
        'svg_path' => 'icons/*.svg',
        'dest'     => '',
        'flatten'  => true,
    ],
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function info(string $msg): void
{
    echo "\033[32m[INFO]\033[0m  {$msg}\n";
}

function warn(string $msg): void
{
    echo "\033[33m[WARN]\033[0m  {$msg}\n";
}

function error(string $msg): void
{
    echo "\033[31m[ERROR]\033[0m {$msg}\n";
}

function heading(string $msg): void
{
    echo "\n\033[1;34m==> {$msg}\033[0m\n";
}

/**
 * Fetch the latest version of an npm package from the registry.
 */
function fetchLatestVersion(string $package): ?string
{
    $encodedPackage = str_replace('/', '%2F', $package);
    $url = "https://registry.npmjs.org/{$encodedPackage}/latest";

    $ctx = stream_context_create(['http' => [
        'timeout' => 30,
        'header'  => "User-Agent: swarm-icons-build/1.0\r\n",
    ]]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        return null;
    }

    $data = json_decode($json, true);
    return is_array($data) ? ($data['version'] ?? null) : null;
}

/**
 * Download the npm tarball for a package@version and return a local temp file path.
 */
function downloadTarball(string $package, string $version): ?string
{
    $encodedPackage = str_replace('/', '%2F', $package);
    $safeName       = preg_replace('/[^a-z0-9\-]/', '-', strtolower($package)) . '-' . $version;
    $tarball        = sys_get_temp_dir() . '/swarm-icons-build/' . $safeName . '.tgz';

    if (file_exists($tarball)) {
        info("  Using cached tarball: {$tarball}");
        return $tarball;
    }

    @mkdir(dirname($tarball), 0755, true);

    // Fetch tarball URL from registry metadata
    $metaUrl = "https://registry.npmjs.org/{$encodedPackage}/{$version}";
    $ctx     = stream_context_create(['http' => [
        'timeout' => 30,
        'header'  => "User-Agent: swarm-icons-build/1.0\r\n",
    ]]);

    $json = @file_get_contents($metaUrl, false, $ctx);
    if ($json === false) {
        error("  Failed to fetch metadata from {$metaUrl}");
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        error("  Invalid JSON response from registry");
        return null;
    }

    $tarballUrl = $data['dist']['tarball'] ?? null;
    if (!is_string($tarballUrl)) {
        error("  No tarball URL found in registry metadata");
        return null;
    }

    info("  Downloading {$tarballUrl}");

    $ctx = stream_context_create(['http' => [
        'timeout' => 120,
        'header'  => "User-Agent: swarm-icons-build/1.0\r\n",
    ]]);

    $bytes = @file_get_contents($tarballUrl, false, $ctx);
    if ($bytes === false) {
        error("  Download failed");
        return null;
    }

    file_put_contents($tarball, $bytes);
    info("  Downloaded " . number_format(strlen($bytes) / 1024, 1) . " KB");

    return $tarball;
}

/**
 * Extract SVGs matching a glob pattern from a .tgz archive.
 *
 * @return array<string, string> Map of filename => SVG content
 */
function extractSvgsFromTarball(string $tarballPath, string $globPattern): array
{
    if (!extension_loaded('zlib')) {
        error("ext-zlib is required for tarball extraction. Install or enable it in php.ini.");
        exit(1);
    }

    // Decompress gzip to a raw tar stream
    $gz      = gzopen($tarballPath, 'rb');
    if ($gz === false) {
        error("  Cannot open tarball: {$tarballPath}");
        return [];
    }

    $tarContent = '';
    while (!gzeof($gz)) {
        $tarContent .= gzread($gz, 65536);
    }
    gzclose($gz);

    return parseTarForSvgs($tarContent, $globPattern);
}

/**
 * Parse raw TAR content and extract files matching a glob-like pattern.
 *
 * @return array<string, string>
 */
function parseTarForSvgs(string $tarContent, string $globPattern): array
{
    // Convert glob to regex: *.svg → [^/]*\.svg
    $regex = '#' . str_replace(
        ['*', '?', '.'],
        ['[^/]*', '[^/]', '\\.'],
        $globPattern
    ) . '$#i';

    $svgs   = [];
    $offset = 0;
    $length = strlen($tarContent);

    while ($offset + 512 <= $length) {
        $header = substr($tarContent, $offset, 512);

        // End-of-archive: two 512-byte zero blocks
        if (trim($header) === '') {
            break;
        }

        // TAR header fields
        $nameRaw  = rtrim(substr($header, 0, 100), "\0");
        $sizeOctal = trim(substr($header, 124, 12));
        $typeFlag  = $header[156];

        $size = octdec($sizeOctal);
        if (!is_int($size) && !is_float($size)) {
            $offset += 512;
            continue;
        }
        $size = (int) $size;

        $offset += 512; // Skip past header block

        if ($typeFlag === '0' || $typeFlag === "\0") {
            // Strip leading "package/" that npm adds to all paths
            $name = preg_replace('#^[^/]+/#', '', $nameRaw) ?? $nameRaw;

            if (preg_match($regex, $name) && $size > 0) {
                $content = substr($tarContent, $offset, $size);
                $svgs[basename($name)] = $content;
            }
        }

        // Advance to next 512-byte-aligned block
        $offset += (int) (ceil($size / 512) * 512);
    }

    return $svgs;
}

/**
 * Write extracted SVGs to the destination directory.
 *
 * @param array<string, string> $svgs
 */
function writeSvgs(array $svgs, string $destDir): int
{
    @mkdir($destDir, 0755, true);

    $count = 0;
    foreach ($svgs as $filename => $content) {
        $dest = $destDir . '/' . $filename;
        file_put_contents($dest, $content);
        $count++;
    }

    return $count;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$packagesRoot = dirname(__DIR__) . '/packages';

// Determine which sets to build
$argv = $_SERVER['argv'] ?? [];
array_shift($argv); // remove script name

if (empty($argv)) {
    $selected = array_keys($iconSets);
} else {
    $selected = [];
    foreach ($argv as $key) {
        if (!isset($iconSets[$key])) {
            error("Unknown icon set: '{$key}'. Available: " . implode(', ', array_keys($iconSets)));
            exit(1);
        }
        $selected[] = $key;
    }
}

$totalIcons = 0;

foreach ($selected as $key) {
    $config  = $iconSets[$key];
    $package = $config['package'];

    heading("Building {$key} ({$package})");

    // Resolve version
    info("Fetching latest version of {$package}...");
    $version = fetchLatestVersion($package);
    if ($version === null) {
        error("Could not determine latest version. Skipping.");
        continue;
    }
    info("  Version: {$version}");

    // Download tarball
    $tarball = downloadTarball($package, $version);
    if ($tarball === null) {
        error("Download failed. Skipping.");
        continue;
    }

    // Extract SVGs
    info("Extracting SVGs matching: {$config['svg_path']}");
    $svgs = extractSvgsFromTarball($tarball, $config['svg_path']);

    if (empty($svgs)) {
        warn("No SVGs found. Check the svg_path pattern for this package.");
        continue;
    }

    // Write to destination
    $packageDir = "{$packagesRoot}/swarm-icons-{$key}";
    $destDir    = $config['dest'] !== ''
        ? "{$packageDir}/resources/svg/{$config['dest']}"
        : "{$packageDir}/resources/svg";

    $count = writeSvgs($svgs, $destDir);
    $totalIcons += $count;

    info("  Wrote {$count} SVGs to {$destDir}");
}

echo "\n";
info("Done. Total icons written: {$totalIcons}");
echo "\n";
