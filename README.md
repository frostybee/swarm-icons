<p align="center">
  <img src="swarm.svg" alt="SwarmIcons" width="160">
</p>

<h1 align="center">Swarm Icons</h1>
<p align="center"><strong>A Framework-Agnostic PHP Library for Rendering SVG Icons</strong></p>

<p align="center">
  <a href="https://packagist.org/packages/frostybee/swarm-icons"><img src="https://img.shields.io/packagist/v/frostybee/swarm-icons.svg" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/frostybee/swarm-icons"><img src="https://img.shields.io/packagist/php-v/frostybee/swarm-icons.svg" alt="PHP Version"></a>
  <a href="https://packagist.org/packages/frostybee/swarm-icons"><img src="https://img.shields.io/packagist/l/frostybee/swarm-icons.svg" alt="License"></a>
</p>

## Icon Set Packages

Install only the icon sets you need as separate packages:

| Package | Icons | Install |
|---------|------:|---------|
| [swarm-icons-heroicons](https://github.com/swarm-icons/swarm-icons-heroicons) | 324 | `composer require frostybee/swarm-icons-heroicons` |
| [swarm-icons-tabler](https://github.com/swarm-icons/swarm-icons-tabler) | 4,985 | `composer require frostybee/swarm-icons-tabler` |
| [swarm-icons-lucide](https://github.com/swarm-icons/swarm-icons-lucide) | 1,936 | `composer require frostybee/swarm-icons-lucide` |
| [swarm-icons-bootstrap](https://github.com/swarm-icons/swarm-icons-bootstrap) | 2,078 | `composer require frostybee/swarm-icons-bootstrap` |
| [swarm-icons-phosphor](https://github.com/swarm-icons/swarm-icons-phosphor) | 1,512 (regular variant) | `composer require frostybee/swarm-icons-phosphor` |
| [swarm-icons-simple](https://github.com/swarm-icons/swarm-icons-simple) | 3,397 | `composer require frostybee/swarm-icons-simple` |

Each package requires `frostybee/swarm-icons` as a dependency. It will be installed automatically. Or skip the packages entirely and use the [Iconify API](https://iconify.design) at runtime to access 200,000+ icons on demand.

## Installation

Install the core library only if you plan to use the Iconify API at runtime or your own local SVG files. If you want a bundled icon set, install one of the [packages above](#icon-set-packages) instead. The core library will be pulled in automatically.

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
```

| Command | Description |
|---------|-------------|
| `composer test` | Run PHPUnit tests |
| `composer phpstan` | Run PHPStan (level 8) |
| `composer cs-check` | Check code style (dry-run) |
| `composer cs-fix` | Auto-fix code style |
| `composer test-all` | PHPStan + CS check + tests |

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
