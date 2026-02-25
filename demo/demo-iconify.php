<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Frostybee\SwarmIcons\SwarmIconsConfig;
use Frostybee\SwarmIcons\SwarmIcons;

echo "=== SwarmIcons Phase 2 Demo - Iconify Integration ===\n\n";

// Create configuration with Iconify providers
$manager = SwarmIconsConfig::create()
    ->addIconifySet('heroicons')          // Heroicons
    ->addIconifySet('lucide')             // Lucide icons
    ->addIconifySet('tabler')             // Tabler icons
    ->cachePath(__DIR__ . '/cache/icons') // Cache in project directory
    ->defaultPrefix('heroicons')
    ->defaultAttributes(['class' => 'icon'])
    ->build();

// Bootstrap global helper
SwarmIcons::setManager($manager);

echo "🌐 Fetching icons from Iconify API...\n\n";

// ===============================================
// Example 1: Heroicons (solid)
// ===============================================
echo "1️⃣  Heroicons - Home (first call hits API, gets cached):\n";
$start = microtime(true);
$heroicon = swarm_icon('heroicons:home');
$time1 = round((microtime(true) - $start) * 1000, 2);
echo "   Fetched in {$time1}ms\n";
echo "   " . substr($heroicon->toHtml(), 0, 100) . "...\n\n";

// ===============================================
// Example 2: Same icon from cache
// ===============================================
echo "2️⃣  Heroicons - Home (second call uses cache):\n";
$start = microtime(true);
$heroiconCached = swarm_icon('heroicons:home');
$time2 = round((microtime(true) - $start) * 1000, 2);
echo "   Fetched in {$time2}ms (from cache)\n";
echo "   Speed improvement: " . round($time1 / max($time2, 0.01), 1) . "x faster\n\n";

// ===============================================
// Example 3: Lucide icons
// ===============================================
echo "3️⃣  Lucide - User icon:\n";
$lucide = swarm_icon('lucide:user', ['class' => 'w-6 h-6']);
echo "   " . substr($lucide->toHtml(), 0, 100) . "...\n\n";

// ===============================================
// Example 4: Tabler icons
// ===============================================
echo "4️⃣  Tabler - Settings icon:\n";
$tabler = swarm_icon('tabler:settings', ['class' => 'w-6 h-6']);
echo "   " . substr($tabler->toHtml(), 0, 100) . "...\n\n";

// ===============================================
// Example 5: Icon with custom styling
// ===============================================
echo "5️⃣  Styled icon (Heroicons - Heart):\n";
$styledIcon = $manager->get('heroicons:heart')
    ->size(32)
    ->class('text-red-500')
    ->fill('currentColor');
echo "   " . substr($styledIcon->toHtml(), 0, 100) . "...\n\n";

// ===============================================
// Example 6: Check what's been cached
// ===============================================
echo "6️⃣  Cache statistics:\n";
$cacheDir = __DIR__ . '/cache/icons';
if (is_dir($cacheDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS)
    );
    $count = 0;
    $size = 0;
    foreach ($files as $file) {
        if ($file->isFile()) {
            $count++;
            $size += $file->getSize();
        }
    }
    echo "   Cached files: {$count}\n";
    echo "   Total size: " . round($size / 1024, 2) . " KB\n";
    echo "   Cache location: {$cacheDir}\n";
} else {
    echo "   Cache directory not created yet\n";
}
echo "\n";

// ===============================================
// Example 7: Using different icon sets
// ===============================================
echo "7️⃣  Multiple icon sets:\n";
echo "   Heroicons (solid): " . ($manager->has('heroicons:home') ? '✓' : '✗') . " Available\n";
echo "   Lucide: " . ($manager->has('lucide:user') ? '✓' : '✗') . " Available\n";
echo "   Tabler: " . ($manager->has('tabler:settings') ? '✓' : '✗') . " Available\n";
echo "\n";

echo "✅ Demo complete! Icons are now cached for future use.\n";
echo "💡 Try running this script again - subsequent runs will be much faster!\n";
