# swarm-icons-lucide

Lucide Icons for the [frostybee/swarm-icons](https://github.com/swarm-icons/swarm-icons) PHP library. Provides ~1,900 SVG icons from the [Lucide](https://lucide.dev) set, a community fork of Feather Icons with a much larger and actively maintained collection.

## Installation

```bash
composer require frostybee/swarm-icons-lucide
```

This will pull in `frostybee/swarm-icons` automatically.

## Usage

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addIconSet('lucide')
    ->build();

echo $manager->get('lucide:home');
echo $manager->get('lucide:home', ['class' => 'w-6 h-6', 'aria-label' => 'Home']);
```

Icons are prefixed with `lucide`. You can also use the global helper:

```php
echo icon('lucide:home');
echo icon('lucide:settings', ['class' => 'w-5 h-5']);
```

## Twig

```twig
{{ icon('lucide:home') }}
{{ icon('lucide:settings', {class: 'w-6 h-6'}) }}
```

## License

MIT. The Lucide SVG files are licensed under the [ISC License](https://github.com/lucide-icons/lucide/blob/main/LICENSE).
