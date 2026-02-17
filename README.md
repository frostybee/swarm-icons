# SwarmIcons

A PHP library for rendering SVG icons. Works with any PHP project, supports local icon files and the Iconify API, and includes a Twig extension.

[![Latest Version](https://img.shields.io/packagist/v/frostybee/swarm-icons.svg)](https://packagist.org/packages/frostybee/swarm-icons)
[![PHP Version](https://img.shields.io/packagist/php-v/frostybee/swarm-icons.svg)](https://packagist.org/packages/frostybee/swarm-icons)
[![License](https://img.shields.io/packagist/l/frostybee/swarm-icons.svg)](https://packagist.org/packages/frostybee/swarm-icons)

## Installation

```bash
composer require frostybee/swarm-icons
```

Requires PHP 8.2+ and `psr/simple-cache` ^3.0.

## Quick Start

Register a directory of SVG files, then render icons by name:

```php
use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use Frostybee\SwarmIcons\SwarmIcons;

$manager = new IconManager();
$manager->register('custom', new DirectoryProvider('/path/to/svgs'));
$manager->setDefaultPrefix('custom');

echo $manager->get('home', ['class' => 'w-6 h-6']);
// <svg class="w-6 h-6" aria-hidden="true">...</svg>
```

You can also set up a global helper for use throughout your app:

```php
SwarmIcons::setManager($manager);

echo icon('home');
echo icon('custom:home', ['class' => 'w-6 h-6', 'aria-label' => 'Home']);
```

## Fluent Builder

The config builder is the recommended way to set things up:

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addDirectory('custom', '/path/to/svgs')
    ->addIconifySet('heroicons')
    ->addIconifySet('tabler')
    ->cachePath('/var/cache/icons')
    ->defaultPrefix('custom')
    ->defaultAttributes(['class' => 'icon'])
    ->prefixAttributes('tabler', ['stroke-width' => '1.5'])
    ->fallbackIcon('heroicons:question-mark')
    ->build();
```

## Icon Providers

### Local SVG files

`DirectoryProvider` maps icon names to `.svg` files on disk. Subdirectory notation is supported:

```php
$provider = new DirectoryProvider('/path/to/svgs', recursive: true);
$manager->register('custom', $provider);

$manager->get('custom:home');           // home.svg
$manager->get('custom:outline/user');   // outline/user.svg
```

### Iconify API

`IconifyProvider` fetches icons from [api.iconify.design](https://iconify.design) and caches them locally. Over 200,000 icons are available across 100+ icon sets.

```php
use Frostybee\SwarmIcons\Provider\IconifyProvider;
use Frostybee\SwarmIcons\Cache\FileCache;

$provider = new IconifyProvider(
    prefix: 'heroicons',
    cache: new FileCache('/var/cache/icons'),
    timeout: 10,
    cacheTtl: 0  // 0 = cache forever
);

$manager->register('heroicons', $provider);
```

Some popular sets: `heroicons`, `lucide`, `tabler`, `bootstrap`, `phosphor`, `simple-icons`.

### Hybrid (local + Iconify fallback)

`ChainProvider` tries local files first and falls back to the Iconify API:

```php
use Frostybee\SwarmIcons\Provider\ChainProvider;

$manager->register('icons', new ChainProvider([
    new DirectoryProvider('/path/to/custom/icons'),
    new IconifyProvider('heroicons', new FileCache('/tmp/cache')),
]));
```

Or via the builder:

```php
SwarmIconsConfig::create()
    ->addHybridSet('icons', '/path/to/custom/icons')
    ->build();
```

## Fluent API

Icons are immutable value objects. Every method returns a new instance:

```php
echo $manager->get('home')
    ->size(32)
    ->class('text-blue-500')
    ->strokeWidth(1.5)
    ->fill('currentColor');
```

## Twig

Register the extension and use `icon()`, `icon_exists()`, and `get_icon()` in your templates:

```php
use Frostybee\SwarmIcons\Twig\SwarmIconsExtension;
use Frostybee\SwarmIcons\Twig\SwarmIconsRuntime;

$twig->addExtension(new SwarmIconsExtension(
    new SwarmIconsRuntime($manager, silentOnMissing: true)
));
```

```twig
{{ icon('heroicons:home') }}
{{ icon('heroicons:home', {class: 'w-6 h-6', 'aria-label': 'Home'}) }}

{% if icon_exists('heroicons:user') %}
    {{ icon('heroicons:user') }}
{% endif %}

{% set star = get_icon('heroicons:star') %}
{{ star.size(32).class('text-yellow-500') }}
```

## Accessibility

Decorative icons get `aria-hidden="true"` automatically. Add `aria-label` to mark an icon as meaningful:

```php
echo icon('home');
// <svg aria-hidden="true">...</svg>

echo icon('home', ['aria-label' => 'Home']);
// <svg role="img" aria-label="Home">...</svg>
```

## Development

```bash
composer install
composer test
composer phpstan
composer test-all
```

## License

MIT. See [LICENSE](LICENSE) for details.

## Credits

Created by **frostybee**.
