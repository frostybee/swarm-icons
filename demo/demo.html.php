<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\IconRenderer;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\SwarmIcons;

// Setup
$manager = new IconManager();
$provider = new DirectoryProvider(__DIR__ . '/tests/Fixtures/icons');
$manager->register('test', $provider);
$manager->setDefaultPrefix('test');

$renderer = new IconRenderer(['class' => 'icon']);
$manager->setRenderer($renderer);

SwarmIcons::setManager($manager);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwarmIcons Demo</title>
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

        .w-6 { width: 24px; }
        .h-6 { height: 24px; }
        .w-8 { width: 32px; }
        .h-8 { height: 32px; }
        .w-12 { width: 48px; }
        .h-12 { height: 48px; }
        .w-16 { width: 64px; }
        .h-16 { height: 64px; }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        <h1>🐝 SwarmIcons Demo</h1>
        <p class="subtitle">Framework-agnostic PHP SVG icon library</p>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= count(iterator_to_array($manager->all('test'))) ?></div>
                <div class="stat-label">Test Icons Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">70</div>
                <div class="stat-label">Unit Tests Passing</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Level 8</div>
                <div class="stat-label">PHPStan Strict</div>
            </div>
        </div>

        <!-- Example 1: Basic Icons -->
        <div class="example">
            <h2>1. Basic Icon Rendering</h2>
            <p class="example-description">Simple icon output with default styling</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= icon('home') ?>
                    <span class="icon-label">Home</span>
                </div>
                <div class="icon-item">
                    <?= icon('user') ?>
                    <span class="icon-label">User</span>
                </div>
            </div>
            <code>&lt;?= icon('home') ?&gt;</code>
        </div>

        <!-- Example 2: Size Variations -->
        <div class="example">
            <h2>2. Size Variations</h2>
            <p class="example-description">Different icon sizes using fluent API</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= $manager->get('home')->size(16) ?>
                    <span class="icon-label">16px</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-6 h-6']) ?>
                    <span class="icon-label">24px</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-8 h-8']) ?>
                    <span class="icon-label">32px</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-12 h-12']) ?>
                    <span class="icon-label">48px</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-16 h-16']) ?>
                    <span class="icon-label">64px</span>
                </div>
            </div>
            <code>&lt;?= icon('home', ['class' => 'w-8 h-8']) ?&gt;</code>
        </div>

        <!-- Example 3: Color Variations -->
        <div class="example">
            <h2>3. Color Variations</h2>
            <p class="example-description">Custom colors using stroke attribute</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-12 h-12 text-blue-500']) ?>
                    <span class="icon-label">Blue</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-12 h-12 text-red-500']) ?>
                    <span class="icon-label">Red</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-12 h-12 text-green-500']) ?>
                    <span class="icon-label">Green</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-12 h-12 text-purple-500']) ?>
                    <span class="icon-label">Purple</span>
                </div>
            </div>
            <code>&lt;?= icon('home', ['class' => 'w-12 h-12 text-blue-500']) ?&gt;</code>
        </div>

        <!-- Example 4: Fluent API -->
        <div class="example">
            <h2>4. Fluent API</h2>
            <p class="example-description">Chaining methods for attribute manipulation</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= $manager->get('user')
                        ->size(48)
                        ->class('text-blue-500')
                        ->fill('currentColor')
                        ->stroke('none') ?>
                    <span class="icon-label">Fluent API</span>
                </div>
            </div>
            <code>$manager->get('user')
    ->size(48)
    ->class('text-blue-500')
    ->fill('currentColor')
    ->stroke('none')</code>
        </div>

        <!-- Example 5: Accessibility -->
        <div class="example">
            <h2>5. Accessibility (ARIA)</h2>
            <p class="example-description">Automatic aria-hidden for decorative icons, role="img" for labeled icons</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-12 h-12']) ?>
                    <span class="icon-label">Decorative<br>(aria-hidden)</span>
                </div>
                <div class="icon-item">
                    <?= icon('home', ['class' => 'w-12 h-12', 'aria-label' => 'Home']) ?>
                    <span class="icon-label">Labeled<br>(role="img")</span>
                </div>
            </div>
            <code>// Decorative: &lt;svg aria-hidden="true"&gt;...
// Labeled: &lt;svg role="img" aria-label="Home"&gt;...</code>
        </div>

        <!-- Example 6: Stroke Width -->
        <div class="example">
            <h2>6. Stroke Width Variations</h2>
            <p class="example-description">Adjusting line thickness</p>
            <div class="icon-display">
                <div class="icon-item">
                    <?= $manager->get('home')->size(48)->strokeWidth(1) ?>
                    <span class="icon-label">Thin (1)</span>
                </div>
                <div class="icon-item">
                    <?= $manager->get('home')->size(48)->strokeWidth(1.5) ?>
                    <span class="icon-label">Normal (1.5)</span>
                </div>
                <div class="icon-item">
                    <?= $manager->get('home')->size(48)->strokeWidth(2) ?>
                    <span class="icon-label">Default (2)</span>
                </div>
                <div class="icon-item">
                    <?= $manager->get('home')->size(48)->strokeWidth(3) ?>
                    <span class="icon-label">Bold (3)</span>
                </div>
            </div>
            <code>&lt;?= $manager->get('home')->strokeWidth(1.5) ?&gt;</code>
        </div>

        <!-- Example 7: HTML Code -->
        <div class="example">
            <h2>7. Generated HTML</h2>
            <p class="example-description">The actual SVG markup produced</p>
            <code><?= htmlspecialchars(icon('home', ['class' => 'w-6 h-6'])->toHtml()) ?></code>
        </div>
    </div>
</body>
</html>
