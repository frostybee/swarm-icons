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

<p align="center">
  <a href="https://frostybee.github.io/swarm-icons/"><img src="https://img.shields.io/badge/docs-swarm--icons-blue" alt="Documentation"></a>
</p>

## Features

- Access **200,000+ icons** from 200+ [Iconify](https://iconify.design) sets, downloadable via CLI (no Node.js required)
- Load icons from local SVG directories, the Iconify API, JSON collections, or a hybrid of local files with API fallback
- Style icons with an immutable fluent API: `size()`, `class()`, `fill()`, `stroke()`, `rotate()`, `flip()`, and more
- Integrates with Twig, Laravel Blade, Slim, and CommonMark
- Deduplicate repeated icons on a page using SVG sprite sheets with `<symbol>` / `<use>` references
- Layer multiple icons into a single composite SVG with icon stacking
- Decorative icons get `aria-hidden="true"` automatically; labeled icons get `role="img"`
- SVG content is sanitized: scripts, event handlers, and external resources are stripped
- PSR-16 file-based caching with configurable TTL

## Installation

```bash
composer require frostybee/swarm-icons
```

Requires PHP 8.2+ and `psr/simple-cache` ^3.0.

## Quick Start

Download icon sets and render:

```bash
php bin/swarm-icons json:download mdi tabler heroicons
```

```php
use Frostybee\SwarmIcons\SwarmIcons;
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->discoverJsonSets()
    ->cachePath('/var/cache/icons')
    ->build();

SwarmIcons::setManager($manager);

echo swarm_icon('mdi:home', ['class' => 'w-6 h-6']);
// <svg class="w-6 h-6" aria-hidden="true">...</svg>
```

You can also register local SVG directories or fetch from the Iconify API at runtime:

```php
$manager = SwarmIconsConfig::create()
    ->addDirectory('custom', '/path/to/svgs')
    ->addIconifySet('heroicons')
    ->defaultAttributes(['class' => 'icon'])
    ->prefixAttributes('heroicons', ['stroke' => 'currentColor'])
    ->fallbackIcon('heroicons:question-mark-circle')
    ->build();
```

## Documentation

For detailed guides on configuration, providers, CLI commands, framework integrations, and advanced options, visit the **[full documentation](https://frostybee.github.io/swarm-icons/)**.

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
