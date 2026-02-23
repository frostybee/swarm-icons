# swarm-icons-tabler

Tabler Icons for the [frostybee/swarm-icons](https://github.com/swarm-icons/swarm-icons) PHP library. Provides ~5,900 SVG icons from the [Tabler Icons](https://tabler.io/icons) set, one of the largest and most actively maintained open source icon libraries available.

## Installation

```bash
composer require frostybee/swarm-icons-tabler
```

This will pull in `frostybee/swarm-icons` automatically.

## Usage

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addIconSet('tabler')
    ->build();

echo $manager->get('tabler:home');
echo $manager->get('tabler:home', ['class' => 'w-6 h-6', 'stroke-width' => '1.5']);
```

Icons are prefixed with `tabler`. You can also use the global helper:

```php
echo icon('tabler:home');
echo icon('tabler:settings', ['class' => 'w-5 h-5', 'stroke-width' => '1.5']);
```

## Twig

```twig
{{ icon('tabler:home') }}
{{ icon('tabler:settings', {class: 'w-6 h-6', 'stroke-width': '1.5'}) }}
```

## License

MIT. The Tabler Icons SVG files are licensed under the [MIT License](https://github.com/tabler/tabler-icons/blob/master/LICENSE).
