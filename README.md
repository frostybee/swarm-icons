# SwarmIcons

**Framework-agnostic PHP SVG icon library with first-class Twig support and Iconify API integration.**

[![Latest Version](https://img.shields.io/packagist/v/frostybee/swarm-icons.svg)](https://packagist.org/packages/frostybee/swarm-icons)
[![PHP Version](https://img.shields.io/packagist/php-v/frostybee/swarm-icons.svg)](https://packagist.org/packages/frostybee/swarm-icons)
[![License](https://img.shields.io/packagist/l/frostybee/swarm-icons.svg)](https://packagist.org/packages/frostybee/swarm-icons)

## Features

- 🎨 **Framework-agnostic** — Works with any PHP project (no Laravel/Symfony required)
- 🔥 **Twig integration** — Native `icon()` function in templates
- 🌐 **Iconify API support** — Access 200,000+ icons with automatic caching
- 📁 **Local SVG files** — Use your own custom icons
- ♿ **Accessibility** — Automatic ARIA attributes (decorative vs. labeled icons)
- 🎯 **Type-safe** — PHP 8.2+ with strict types, PHPStan level 8
- 🔧 **Fluent API** — Chainable methods for attribute manipulation
- 🚀 **Zero dependencies** — Only requires `psr/simple-cache`

## Installation

```bash
composer require frostybee/swarm-icons
```

## Quick Start

### Basic Usage

```php
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\SwarmIcons;

// Create manager
$manager = new IconManager();

// Register a local directory provider
$manager->register('custom', new DirectoryProvider('/path/to/svgs'));
$manager->setDefaultPrefix('custom');

// Get an icon
$icon = $manager->get('home', ['class' => 'w-6 h-6']);

echo $icon; // <svg class="w-6 h-6" aria-hidden="true">...</svg>
```

### Global Helper Function

```php
// Bootstrap once
SwarmIcons::setManager($manager);

// Use anywhere in your app
echo icon('custom:home');
echo icon('custom:home', ['class' => 'w-6 h-6', 'aria-label' => 'Home']);
```

### Twig Integration ✨

```php
use Frostybee\SwarmIcons\Twig\SwarmIconsExtension;
use Frostybee\SwarmIcons\Twig\SwarmIconsRuntime;

// Register extension
$twig->addExtension(new SwarmIconsExtension(
    new SwarmIconsRuntime($manager, silentOnMissing: true)
));
```

**In your templates:**

```twig
{# Basic usage #}
{{ icon('heroicons:home') }}

{# With custom attributes #}
{{ icon('heroicons:home', {class: 'w-6 h-6', 'aria-label': 'Home'}) }}

{# Conditional rendering #}
{% if icon_exists('heroicons:user') %}
    {{ icon('heroicons:user') }}
{% endif %}

{# Advanced manipulation #}
{% set myIcon = get_icon('heroicons:star') %}
{{ myIcon.size(32).class('text-yellow-500') }}
```

**Available Twig functions:**

- `icon(name, attributes)` - Render an icon
- `icon_exists(name)` - Check if icon exists
- `get_icon(name, attributes)` - Get icon object for manipulation

### Fluent Icon Manipulation

```php
$icon = $manager->get('home')
    ->size(32)
    ->class('text-blue-500')
    ->strokeWidth(1.5)
    ->fill('currentColor');

echo $icon;
```

## Configuration

### Using the Fluent Builder ✨

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addDirectory('custom', '/path/to/svgs')
    ->addIconifySet('heroicons')         // Access 200,000+ icons!
    ->addIconifySet('lucide')
    ->addIconifySet('tabler')
    ->cachePath('/var/cache/icons')      // Cache icons on disk
    ->defaultPrefix('custom')
    ->defaultAttributes(['class' => 'icon'])
    ->prefixAttributes('tabler', ['stroke-width' => '1.5'])
    ->fallbackIcon('heroicons:question-mark')
    ->build();
```

## Icon Providers

### DirectoryProvider (Local SVG Files)

Maps icon names to `.svg` files in a directory.

```php
use Frostybee\SwarmIcons\Provider\DirectoryProvider;

$provider = new DirectoryProvider(
    directory: '/path/to/svgs',
    recursive: true,  // Scan subdirectories
    extension: 'svg'
);

$manager->register('custom', $provider);

// Access icons
$icon = $manager->get('custom:home');           // → home.svg
$icon = $manager->get('custom:outline/user');   // → outline/user.svg
```

### IconifyProvider (Iconify API with Caching) ✨

Access 200,000+ icons from popular icon sets without bundling any files!

```php
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use Frostybee\SwarmIcons\Cache\FileCache;

$cache = new FileCache('/var/cache/icons');

$provider = new IconifyProvider(
    prefix: 'heroicons',     // Icon set: heroicons, lucide, tabler, bootstrap, etc.
    cache: $cache,
    timeout: 10,             // HTTP timeout in seconds
    cacheTtl: 0              // Cache forever (0 = infinite)
);

$manager->register('heroicons', $provider);

// First request: fetches from API (~200ms)
$icon = $manager->get('heroicons:home');

// Subsequent requests: instant from cache (~0.5ms)
$icon = $manager->get('heroicons:home');
```

**Popular Icon Sets Available:**

- `heroicons` - 450+ beautiful hand-crafted icons
- `lucide` - 1,500+ consistent icons
- `tabler` - 5,900+ pixel-perfect icons
- `bootstrap` - 2,000+ Bootstrap Icons
- `phosphor` - 7,000+ flexible icons
- `simple-icons` - 2,200+ brand icons
- And 100+ more sets!

### ChainProvider (Hybrid: Local + Iconify Fallback) ✨

Try local files first, fallback to Iconify if not found.

```php
use Frostybee\SwarmIcons\Provider\ChainProvider;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use Frostybee\SwarmIcons\Cache\FileCache;

$chain = new ChainProvider([
    new DirectoryProvider('/path/to/custom/icons'),  // Try local first
    new IconifyProvider('heroicons', new FileCache('/tmp/cache'))  // Fallback to API
]);

$manager->register('icons', $chain);

// Uses local if exists, otherwise fetches from Iconify
$icon = $manager->get('icons:home');
```

Or use the fluent builder:

```php
$manager = SwarmIconsConfig::create()
    ->addHybridSet('icons', '/path/to/custom/icons')  // Auto-creates ChainProvider
    ->build();
```

## Accessibility

SwarmIcons automatically applies ARIA attributes:

```php
// Decorative icon (no label)
echo icon('home');
// → <svg aria-hidden="true">...</svg>

// Labeled icon
echo icon('home', ['aria-label' => 'Home']);
// → <svg role="img" aria-label="Home">...</svg>
```

## Icon Set Packages (Coming in Phase 5)

Install pre-packaged icon sets:

```bash
composer require frostybee/swarm-icons-tabler      # 5,900+ icons
composer require frostybee/swarm-icons-heroicons   # 450+ icons
composer require frostybee/swarm-icons-lucide      # 1,500+ icons
```

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer phpstan

# Run all checks
composer test-all
```

## Requirements

- PHP 8.2+
- `psr/simple-cache` ^3.0

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Created by **frostybee** for the PHP community.
