<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Frostybee\SwarmIcons\SwarmIconsConfig;
use Frostybee\SwarmIcons\SwarmIcons;

$jsonFile = __DIR__ . '/../tests/Fixtures/test-collection.json';

// Setup
$manager = SwarmIconsConfig::create()
    ->addJsonCollection('test', $jsonFile)
    ->defaultPrefix('test')
    ->defaultAttributes(['class' => 'icon'])
    ->build();

SwarmIcons::setManager($manager);

// Gather stats
$allIcons = iterator_to_array($manager->all('test'));
$jsonData = json_decode(file_get_contents($jsonFile), true);
$iconCount = count($jsonData['icons'] ?? []);
$aliasCount = count($jsonData['aliases'] ?? []);
$defaultWidth = $jsonData['width'] ?? '—';
$defaultHeight = $jsonData['height'] ?? '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwarmIcons - JsonCollectionProvider Demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2563eb;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .badge {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .example {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .example h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .example-description {
            color: #6b7280;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .icon-display {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .icon {
            stroke: currentColor;
        }

        .icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: white;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }

        .icon-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .alias-arrow {
            font-size: 1.5rem;
            color: #9ca3af;
        }

        code {
            background: #1f2937;
            color: #f9fafb;
            padding: 1rem;
            border-radius: 4px;
            display: block;
            overflow-x: auto;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .text-blue-500 { color: #3b82f6; }
        .text-red-500 { color: #ef4444; }
        .text-green-500 { color: #10b981; }
        .text-purple-500 { color: #8b5cf6; }
        .text-yellow-500 { color: #eab308; }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: #eff6ff;
            padding: 1.5rem;
            border-radius: 6px;
            border: 1px solid #dbeafe;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2563eb;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SwarmIcons - JsonCollectionProvider Demo</h1>
        <p class="subtitle">
            Offline icon rendering from Iconify JSON collections
            <span class="badge">No API calls</span>
        </p>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= $iconCount ?></div>
                <div class="stat-label">Icons</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $aliasCount ?></div>
                <div class="stat-label">Aliases</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($allIcons) ?></div>
                <div class="stat-label">Total Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $defaultWidth ?>x<?= $defaultHeight ?></div>
                <div class="stat-label">Default Dimensions</div>
            </div>
        </div>

        <!-- Example 1: All Icons -->
        <div class="example">
            <h2>1. All Icons in Collection</h2>
            <p class="example-description">Every icon loaded from the JSON collection file</p>
            <div class="icon-display">
                <?php foreach (['home', 'user', 'star'] as $name): ?>
                <div class="icon-item">
                    <?= $manager->get($name)->size(32) ?>
                    <span class="icon-label"><?= $name ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <code>$manager = SwarmIconsConfig::create()
    ->addJsonCollection('test', 'path/to/collection.json')
    ->build();</code>
        </div>

        <!-- Example 2: Alias Resolution -->
        <div class="example">
            <h2>2. Alias Resolution</h2>
            <p class="example-description">Aliases reference parent icons — the provider resolves them transparently</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= icon('home')->size(32) ?>
                    <span class="icon-label">home (original)</span>
                </div>
                <span class="alias-arrow">&rarr;</span>
                <div class="icon-item">
                    <?= icon('house')->size(32) ?>
                    <span class="icon-label">house (alias)</span>
                </div>
            </div>
            <div class="icon-display">
                <div class="icon-item">
                    <?= icon('user')->size(32) ?>
                    <span class="icon-label">user (original)</span>
                </div>
                <span class="alias-arrow">&rarr;</span>
                <div class="icon-item">
                    <?= icon('person')->size(32) ?>
                    <span class="icon-label">person (alias + hFlip)</span>
                </div>
            </div>
            <div class="icon-display">
                <div class="icon-item">
                    <?= icon('home')->size(32) ?>
                    <span class="icon-label">home</span>
                </div>
                <span class="alias-arrow">&rarr;</span>
                <div class="icon-item">
                    <?= icon('house')->size(32) ?>
                    <span class="icon-label">house</span>
                </div>
                <span class="alias-arrow">&rarr;</span>
                <div class="icon-item">
                    <?= icon('chained-alias')->size(32) ?>
                    <span class="icon-label">chained-alias</span>
                </div>
            </div>
            <code>// "house" is an alias for "home"
// "chained-alias" is an alias for "house" (which resolves to "home")</code>
        </div>

        <!-- Example 3: Size Variations -->
        <div class="example">
            <h2>3. Size Variations</h2>
            <p class="example-description">Icons scale cleanly using the fluent size() API</p>
            <div class="icon-display">
                <?php foreach ([16, 24, 32, 48, 64] as $size): ?>
                <div class="icon-item">
                    <?= icon('star')->size($size) ?>
                    <span class="icon-label"><?= $size ?>px</span>
                </div>
                <?php endforeach; ?>
            </div>
            <code>&lt;?= icon('star')->size(48) ?&gt;</code>
        </div>

        <!-- Example 4: Color Variations -->
        <div class="example">
            <h2>4. Color Variations</h2>
            <p class="example-description">Apply colors via CSS classes or the stroke/fill fluent API</p>
            <div class="icon-display">
                <?php
                $colors = [
                    'Blue' => 'text-blue-500',
                    'Red' => 'text-red-500',
                    'Green' => 'text-green-500',
                    'Purple' => 'text-purple-500',
                    'Yellow' => 'text-yellow-500',
                ];
                foreach ($colors as $label => $class): ?>
                <div class="icon-item">
                    <?= icon('home')->size(32)->class($class) ?>
                    <span class="icon-label"><?= $label ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <code>&lt;?= icon('home')->size(32)->class('text-blue-500') ?&gt;</code>
        </div>

        <!-- Example 5: Fluent API -->
        <div class="example">
            <h2>5. Fluent API Chaining</h2>
            <p class="example-description">Chain multiple attribute methods for full control</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= $manager->get('star')
                        ->size(48)
                        ->class('text-yellow-500')
                        ->fill('currentColor')
                        ->stroke('none') ?>
                    <span class="icon-label">Filled Star</span>
                </div>
                <div class="icon-item">
                    <?= $manager->get('home')
                        ->size(48)
                        ->class('text-blue-500')
                        ->strokeWidth(2) ?>
                    <span class="icon-label">Thick Stroke</span>
                </div>
            </div>
            <code>$manager->get('star')
    ->size(48)
    ->class('text-yellow-500')
    ->fill('currentColor')
    ->stroke('none')</code>
        </div>

        <!-- Example 6: Dimension Overrides -->
        <div class="example">
            <h2>6. Dimension Defaults vs Overrides</h2>
            <p class="example-description">Root-level defaults (24x24) are applied unless the icon specifies its own</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= icon('home')->size(48) ?>
                    <span class="icon-label">home<br>viewBox: <?= $manager->get('home')->getAttribute('viewBox') ?></span>
                </div>
                <div class="icon-item">
                    <?= icon('user')->size(48) ?>
                    <span class="icon-label">user<br>viewBox: <?= $manager->get('user')->getAttribute('viewBox') ?></span>
                </div>
            </div>
            <code>// "home" uses root defaults: viewBox="0 0 24 24"
// "user" has its own dimensions: viewBox="0 0 32 32"</code>
        </div>

        <!-- Example 7: Generated HTML -->
        <div class="example">
            <h2>7. Generated HTML</h2>
            <p class="example-description">The actual SVG markup produced by the provider</p>
            <code><?= htmlspecialchars(icon('home')->size(24)->toHtml()) ?></code>
        </div>
    </div>
</body>
</html>
