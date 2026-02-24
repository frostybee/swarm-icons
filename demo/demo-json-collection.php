<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Frostybee\SwarmIcons\SwarmIconsConfig;
use Frostybee\SwarmIcons\SwarmIcons;

echo "=== SwarmIcons Demo - JsonCollectionProvider ===\n\n";

$jsonFile = __DIR__ . '/../tests/Fixtures/test-collection.json';

// Create configuration with JSON collection provider
$manager = SwarmIconsConfig::create()
    ->addJsonCollection('test', $jsonFile)
    ->defaultPrefix('test')
    ->defaultAttributes(['class' => 'icon'])
    ->build();

// Bootstrap global helper
SwarmIcons::setManager($manager);

// ===============================================
// Example 1: List all available icons
// ===============================================
echo "1️⃣  Available icons in collection:\n";
$start = microtime(true);
$allIcons = iterator_to_array($manager->all('test'));
$loadTime = round((microtime(true) - $start) * 1000, 2);
echo "   Icons: " . implode(', ', $allIcons) . "\n";
echo "   Total: " . count($allIcons) . " (loaded in {$loadTime}ms)\n\n";

// ===============================================
// Example 2: Retrieve icons by name
// ===============================================
echo "2️⃣  Retrieve icons by name:\n";
foreach (['home', 'user', 'star'] as $name) {
    $icon = icon($name);
    echo "   {$name}: " . substr($icon->toHtml(), 0, 80) . "...\n";
}
echo "\n";

// ===============================================
// Example 3: Alias resolution
// ===============================================
echo "3️⃣  Alias resolution:\n";
$home = icon('home');
$house = icon('house');
echo "   'house' is an alias for 'home'\n";
echo "   Same content: " . ($home->getContent() === $house->getContent() ? '✓ Yes' : '✗ No') . "\n";

$person = icon('person');
echo "   'person' is an alias for 'user' (with hFlip)\n";
echo "   " . substr($person->toHtml(), 0, 80) . "...\n\n";

// ===============================================
// Example 4: Chained alias resolution
// ===============================================
echo "4️⃣  Chained alias (chained-alias → house → home):\n";
$chained = icon('chained-alias');
echo "   Same content as 'home': " . ($home->getContent() === $chained->getContent() ? '✓ Yes' : '✗ No') . "\n\n";

// ===============================================
// Example 5: Dimension defaults vs overrides
// ===============================================
echo "5️⃣  Dimensions (root defaults vs icon overrides):\n";
$homeIcon = $manager->get('home');
$userIcon = $manager->get('user');
echo "   'home' viewBox: " . $homeIcon->getAttribute('viewBox') . " (root default 24×24)\n";
echo "   'user' viewBox: " . $userIcon->getAttribute('viewBox') . " (icon override 32×32)\n\n";

// ===============================================
// Example 6: Fluent API styling
// ===============================================
echo "6️⃣  Fluent API styling:\n";
$styled = $manager->get('star')
    ->size(48)
    ->class('text-yellow-500')
    ->fill('currentColor');
echo "   " . substr($styled->toHtml(), 0, 120) . "...\n\n";

// ===============================================
// Example 7: Performance — second access is instant
// ===============================================
echo "7️⃣  Performance (JSON already parsed, no re-read):\n";
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $manager->get('home');
}
$time = round((microtime(true) - $start) * 1000, 2);
echo "   1,000 get() calls: {$time}ms\n\n";

// ===============================================
// Example 8: Non-existent icon
// ===============================================
echo "8️⃣  Non-existent icon:\n";
echo "   has('home'): " . ($manager->has('home') ? '✓' : '✗') . "\n";
echo "   has('nonexistent'): " . ($manager->has('nonexistent') ? '✓' : '✗') . "\n\n";

echo "✅ Demo complete! JsonCollectionProvider works offline — no API calls needed.\n";
echo "💡 Use addIconifyJsonSet('tabler') with composer require iconify/json for 200,000+ icons.\n";
