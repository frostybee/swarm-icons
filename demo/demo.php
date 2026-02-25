<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconRenderer;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\SwarmIcons;

echo "=== SwarmIcons Demo ===\n\n";

// 1. Create IconManager
$manager = new IconManager();

// 2. Register a provider for our test fixtures
$provider = new DirectoryProvider(__DIR__ . '/../tests/Fixtures/icons');
$manager->register('test', $provider);
$manager->setDefaultPrefix('test');

// 3. Set up global defaults (optional)
$renderer = new IconRenderer(['class' => 'icon']);
$manager->setRenderer($renderer);

// 4. Bootstrap global helper
SwarmIcons::setManager($manager);

echo "📁 Available icons:\n";
foreach ($manager->all('test') as $iconName) {
    echo "  - {$iconName}\n";
}
echo "\n";

// ===============================================
// Example 1: Basic icon rendering
// ===============================================
echo "1️⃣  Basic icon rendering:\n";
$homeIcon = $manager->get('test:home');
echo $homeIcon->toHtml() . "\n\n";

// ===============================================
// Example 2: Icon with custom attributes
// ===============================================
echo "2️⃣  Icon with custom attributes:\n";
$customIcon = $manager->get('test:home', [
    'width' => '32',
    'height' => '32',
    'class' => 'text-blue-500',
]);
echo $customIcon->toHtml() . "\n\n";

// ===============================================
// Example 3: Using fluent API
// ===============================================
echo "3️⃣  Using fluent API:\n";
$fluentIcon = $manager->get('test:user')
    ->size(48)
    ->class('w-12 h-12')
    ->fill('currentColor')
    ->stroke('none');
echo $fluentIcon->toHtml() . "\n\n";

// ===============================================
// Example 4: Accessibility - Decorative icon
// ===============================================
echo "4️⃣  Decorative icon (aria-hidden):\n";
$decorativeIcon = $manager->get('test:home', ['class' => 'w-6 h-6']);
echo $decorativeIcon->toHtml() . "\n\n";

// ===============================================
// Example 5: Accessibility - Labeled icon
// ===============================================
echo "5️⃣  Labeled icon (role=\"img\"):\n";
$labeledIcon = $manager->get('test:home', [
    'class' => 'w-6 h-6',
    'aria-label' => 'Home',
]);
echo $labeledIcon->toHtml() . "\n\n";

// ===============================================
// Example 6: Using global helper function
// ===============================================
echo "6️⃣  Using global swarm_icon() helper:\n";
echo swarm_icon('home', ['class' => 'w-8 h-8']) . "\n\n";

// ===============================================
// Example 7: Default prefix usage
// ===============================================
echo "7️⃣  Using default prefix:\n";
echo "With prefix: " . swarm_icon('test:user') . "\n";
echo "Without prefix (uses default): " . swarm_icon('user') . "\n\n";

// ===============================================
// Example 8: Icon metadata
// ===============================================
echo "8️⃣  Icon metadata:\n";
$icon = $manager->get('test:home');
echo "Content length: " . strlen($icon->getContent()) . " bytes\n";
echo "Attributes: " . json_encode($icon->getAttributes(), JSON_PRETTY_PRINT) . "\n\n";

// ===============================================
// Example 9: Attribute merging demonstration
// ===============================================
echo "9️⃣  Attribute merging (classes are merged, not replaced):\n";
$mergedIcon = $manager->get('test:home', ['class' => 'custom-class']);
echo "Classes: " . $mergedIcon->getAttribute('class') . "\n";
echo $mergedIcon->toHtml() . "\n\n";

// ===============================================
// Example 10: Provider check
// ===============================================
echo "🔟 Provider checks:\n";
echo "Has 'test:home': " . ($manager->has('test:home') ? 'Yes' : 'No') . "\n";
echo "Has 'test:nonexistent': " . ($manager->has('test:nonexistent') ? 'Yes' : 'No') . "\n";
echo "Has 'unknown:icon': " . ($manager->has('unknown:icon') ? 'Yes' : 'No') . "\n\n";

echo "✅ Demo complete!\n";
