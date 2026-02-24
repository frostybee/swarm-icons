<?php

/**
 * SwarmIcons — JSON Collection Build Script
 *
 * Downloads JSON collections from upstream @iconify-json npm packages
 * and places them into resources/json/.
 *
 * Usage:
 *   php bin/build-icon-sets.php              # Build all JSON sets
 *   php bin/build-icon-sets.php mdi          # Build a single set
 *   php bin/build-icon-sets.php mdi tabler heroicons
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
 * JSON collection set definitions.
 *
 * Each entry maps a prefix to its @iconify-json npm package.
 * The JSON file (icons.json) is extracted and saved to:
 *   resources/json/{prefix}.json
 */
$jsonSets = [
    'mdi'        => ['package' => '@iconify-json/mdi'],
    'fa-solid'   => ['package' => '@iconify-json/fa-solid'],
    'fa-regular' => ['package' => '@iconify-json/fa-regular'],
    'fa-brands'  => ['package' => '@iconify-json/fa-brands'],
    'carbon'     => ['package' => '@iconify-json/carbon'],
    'octicon'    => ['package' => '@iconify-json/octicon'],
    'fluent'     => ['package' => '@iconify-json/fluent'],
    'ion'        => ['package' => '@iconify-json/ion'],
    'ri'         => ['package' => '@iconify-json/ri'],
    'iconoir'    => ['package' => '@iconify-json/iconoir'],
    'mingcute'   => ['package' => '@iconify-json/mingcute'],
    'solar'      => ['package' => '@iconify-json/solar'],
    'uil'        => ['package' => '@iconify-json/uil'],
    'bx'         => ['package' => '@iconify-json/bx'],
    'line-md'    => ['package' => '@iconify-json/line-md'],
    'tabler'     => ['package' => '@iconify-json/tabler'],
    'heroicons'  => ['package' => '@iconify-json/heroicons'],
    'lucide'     => ['package' => '@iconify-json/lucide'],
    'bi'         => ['package' => '@iconify-json/bi'],
    'ph'         => ['package' => '@iconify-json/ph'],
    'simple-icons' => ['package' => '@iconify-json/simple-icons'],
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

if (empty($argv)) {
    $selected = array_keys($jsonSets);
} else {
    $selected = [];
    foreach ($argv as $key) {
        if (!isset($jsonSets[$key])) {
            error("Unknown icon set: '{$key}'. Available: " . implode(', ', array_keys($jsonSets)));
            exit(1);
        }
        $selected[] = $key;
    }
}

$totalJson = 0;

// Build JSON collection sets
$jsonDestDir = dirname(__DIR__) . '/resources/json';

foreach ($selected as $key) {
    $config  = $jsonSets[$key];
    $package = $config['package'];

    heading("Building JSON set: {$key} ({$package})");

    // Resolve version
    info("Fetching latest version of {$package}...");
    $version = $downloader->fetchLatestVersion($package);
    if ($version === null) {
        error("Could not determine latest version. Skipping.");
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
    $destFile = "{$jsonDestDir}/{$key}.json";
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
