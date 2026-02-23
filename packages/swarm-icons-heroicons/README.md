# swarm-icons-heroicons

Heroicons for the [frostybee/swarm-icons](https://github.com/swarm-icons/swarm-icons) PHP library. Provides ~300 SVG icons from the [Heroicons](https://heroicons.com) set by the Tailwind CSS team, ready to render inline with full attribute control.

## Installation

```bash
composer require frostybee/swarm-icons-heroicons
```

This will pull in `frostybee/swarm-icons` automatically.

## Usage

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addIconSet('heroicons')
    ->build();

echo $manager->get('heroicons:home');
echo $manager->get('heroicons:home', ['class' => 'w-6 h-6', 'aria-label' => 'Home']);
```

Icons are prefixed with `heroicons`. You can also use the global helper:

```php
echo icon('heroicons:home');
echo icon('heroicons:user-circle', ['class' => 'w-5 h-5']);
```

## Twig

```twig
{{ icon('heroicons:home') }}
{{ icon('heroicons:user-circle', {class: 'w-6 h-6'}) }}
```

## License

MIT. The Heroicons SVG files are licensed under the [MIT License](https://github.com/tailwindlabs/heroicons/blob/master/LICENSE).
