<?php

/**
 * SwarmIcons — JSON Collection Build Script
 *
 * Downloads JSON collections from upstream @iconify-json npm packages
 * and places them into resources/json/.
 *
 * Any valid Iconify prefix can be used. When run without arguments,
 * builds the popular sets listed below.
 *
 * Usage:
 *   php bin/build-icon-sets.php                    # Build all popular sets
 *   php bin/build-icon-sets.php mdi                # Build a single set
 *   php bin/build-icon-sets.php mdi fluent-emoji   # Build specific sets (any prefix)
 *
 * Requirements: PHP 8.2+, ext-zlib (for gzip decompression)
 */

declare(strict_types=1);

// Find autoloader
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

$autoloaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require $autoloadPath;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    fwrite(STDERR, 'Autoload file not found. Please run: composer install' . PHP_EOL);
    exit(1);
}

use Frostybee\SwarmIcons\Util\NpmDownloader;

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

/**
 * Popular/recommended icon sets built when no arguments are given.
 *
 * Any valid Iconify prefix works — this is just the default set.
 *
 * @var list<string>
 */
$popularSets = [
    'bi',
    'bx',
    'carbon',
    'fa-brands',
    'fa-regular',
    'fa-solid',
    'fa6-brands',
    'fa6-regular',
    'fa6-solid',
    'flowbite',
    'fluent',
    'heroicons',
    'icon-park-outline',
    'iconoir',
    'ion',
    'line-md',
    'lucide',
    'mdi',
    'mingcute',
    'octicon',
    'ph',
    'ri',
    'simple-icons',
    'solar',
    'tabler',
    'uil',
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

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$downloader = new NpmDownloader();

// Determine which sets to build
$argv = $_SERVER['argv'] ?? [];
array_shift($argv); // remove script name

$selected = empty($argv) ? $popularSets : $argv;

$totalJson = 0;

// Build JSON collection sets
$jsonDestDir = dirname(__DIR__) . '/resources/json';

foreach ($selected as $prefix) {
    $package = '@iconify-json/' . $prefix;

    heading("Building JSON set: {$prefix} ({$package})");

    // Resolve version
    info("Fetching latest version of {$package}...");
    $version = $downloader->fetchLatestVersion($package);
    if ($version === null) {
        error("Could not fetch '{$package}' from npm. Check the prefix and try again.");
        continue;
    }
    info("  Version: {$version}");

    // Download tarball
    info("Downloading tarball...");
    $tarball = $downloader->downloadTarball($package, $version);
    if ($tarball === null) {
        error("Download failed. Skipping.");
        continue;
    }

    // Extract icons.json
    info("Extracting icons.json...");
    $content = $downloader->extractFile($tarball, 'icons.json');
    if ($content === null) {
        warn("Could not extract icons.json from tarball. Skipping.");
        continue;
    }

    // Write to destination
    @mkdir($jsonDestDir, 0755, true);
    $destFile = "{$jsonDestDir}/{$prefix}.json";
    file_put_contents($destFile, $content);
    $sizeKb = number_format(strlen($content) / 1024, 1);
    info("  Wrote {$destFile} ({$sizeKb} KB)");
    $totalJson++;
}

echo "\n";
if ($totalJson > 0) {
    info("JSON sets written: {$totalJson}");
}
info("Done.");
echo "\n";
