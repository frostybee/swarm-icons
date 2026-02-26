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
  <a href="https://frostybee.github.io/swarm-icons/">Documentation</a>
</p>

## Installation

```bash
composer require frostybee/swarm-icons
```

Requires PHP 8.2+ and `psr/simple-cache` ^3.0.

## Quick Start

Download icon sets, auto-discover them, and render:

```bash
# Download the sets you need (no Node.js required)
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

You can also register local SVG directories or use the Iconify API at runtime:

```php
$manager = SwarmIconsConfig::create()
    ->addDirectory('custom', '/path/to/svgs')
    ->addIconifySet('heroicons')
    ->build();
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
    ->prefixSuffix('heroicons', 'solid', ['fill' => 'currentColor'])
    ->prefixSuffix('heroicons', 'outline', ['stroke' => 'currentColor', 'fill' => 'none'])
    ->alias('check', 'heroicons:check-circle')
    ->alias('close', 'heroicons:x-mark')
    ->fallbackIcon('heroicons:question-mark-circle')
    ->ignoreNotFound()
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

### Downloading Icon Sets

Any [Iconify](https://iconify.design) icon set can be downloaded as a JSON collection from npm — no Node.js required. Over 200 icon sets and 200,000+ icons are available.

```bash
# Browse all 200+ available Iconify icon sets
php bin/swarm-icons json:browse

# Search by name, prefix, or category
php bin/swarm-icons json:browse --search=emoji
php bin/swarm-icons json:browse --search=Material

# Download specific sets (any valid Iconify prefix)
php bin/swarm-icons json:download mdi fa-solid heroicons

# Download all 22 popular/recommended sets
php bin/swarm-icons json:download --all

# List popular sets with download status
php bin/swarm-icons json:download --list

# Search for icons by name (Iconify API, local directory, or JSON collection)
php bin/swarm-icons icon:search tabler arrow
php bin/swarm-icons icon:search mdi home --json=resources/json/mdi.json
php bin/swarm-icons icon:search custom star --path=./icons
```

Once downloaded, auto-discover and render:

```php
$manager = SwarmIconsConfig::create()
    ->discoverJsonSets()           // auto-registers all downloaded JSON sets
    ->cachePath('/var/cache/icons')
    ->build();

echo swarm_icon('mdi:home');
echo swarm_icon('fa-solid:star');
```

Or register individual sets manually:

```php
$manager = SwarmIconsConfig::create()
    ->addJsonCollection('mdi', '/path/to/mdi.json')
    ->build();
```

Downloaded sets are recorded in a `swarm-icons.json` manifest in your project root. Running `json:download` with no arguments re-downloads everything in the manifest. To automatically restore after `composer install` or `composer update`, add this to your `composer.json`:

```json
"scripts": {
    "post-install-cmd": ["swarm-icons json:download"],
    "post-update-cmd": ["swarm-icons json:download"]
}
```

JSON files are lazy-loaded (parsed only on first icon access) and individual icons are cached via PSR-16, so subsequent requests never touch the JSON file again.

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

## Blade

No extra package is needed. Register the icon manager once in a service provider, then use the global `swarm_icon()` helper in your Blade templates.

**1. Register the manager in `AppServiceProvider`:**

```php
use Frostybee\SwarmIcons\SwarmIcons;
use Frostybee\SwarmIcons\SwarmIconsConfig;

public function register(): void
{
    $manager = SwarmIconsConfig::create()
        ->addIconifySet('heroicons')
        ->build();

    SwarmIcons::setManager($manager);
}
```

**2. Use `swarm_icon()` in your Blade templates:**

```blade
{!! swarm_icon('heroicons:home') !!}
{!! swarm_icon('heroicons:home', ['class' => 'w-6 h-6', 'aria-label' => 'Home']) !!}
{!! swarm_icon('heroicons:home')->size(24)->class('text-blue-500') !!}
```

Use `{!! !!}` (unescaped) since `swarm_icon()` returns raw SVG markup. The standard `{{ }}` syntax would escape the HTML.

## CommonMark

Install the [CommonMark extension](https://github.com/frostybee/swarm-icons-commonmark) to render icons in markdown:

```bash
composer require frostybee/swarm-icons-commonmark
```

```php
use Frostybee\SwarmIcons\CommonMark\IconExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new IconExtension($manager));

$converter = new MarkdownConverter($environment);
```

Then use the `:icon[]` syntax in your markdown:

```markdown
Click :icon[heroicons:home] to go home.
:icon[heroicons:user class="w-6 h-6"] Profile
```

## Accessibility

Decorative icons get `aria-hidden="true"` automatically. Add `aria-label` to mark an icon as meaningful:

```php
echo swarm_icon('home');
// <svg aria-hidden="true">...</svg>

echo swarm_icon('home', ['aria-label' => 'Home']);
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
