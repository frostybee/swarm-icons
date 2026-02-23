# swarm-icons-phosphor

Phosphor Icons for the [frostybee/swarm-icons](https://github.com/swarm-icons/swarm-icons) PHP library. Provides ~1,500 SVG icons (regular variant) from the [Phosphor Icons](https://phosphoricons.com) set, a flexible icon family with a clean and consistent design.

## Installation

```bash
composer require frostybee/swarm-icons-phosphor
```

This will pull in `frostybee/swarm-icons` automatically.

## Usage

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addIconSet('phosphor')
    ->build();

echo $manager->get('phosphor:house');
echo $manager->get('phosphor:house', ['class' => 'w-6 h-6', 'aria-label' => 'Home']);
```

Icons are prefixed with `phosphor`. You can also use the global helper:

```php
echo icon('phosphor:house');
echo icon('phosphor:gear', ['class' => 'w-5 h-5']);
```

## Twig

```twig
{{ icon('phosphor:house') }}
{{ icon('phosphor:gear', {class: 'w-6 h-6'}) }}
```

## License

MIT. The Phosphor Icons SVG files are licensed under the [MIT License](https://github.com/phosphor-icons/core/blob/main/LICENSE).
