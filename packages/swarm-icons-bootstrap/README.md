# swarm-icons-bootstrap

Bootstrap Icons for the [frostybee/swarm-icons](https://github.com/swarm-icons/swarm-icons) PHP library. Provides ~2,000 SVG icons from the [Bootstrap Icons](https://icons.getbootstrap.com) set, ready to render inline with full attribute control.

## Installation

```bash
composer require frostybee/swarm-icons-bootstrap
```

This will pull in `frostybee/swarm-icons` automatically.

## Usage

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addIconSet('bi')
    ->build();

echo $manager->get('bi:alarm');
echo $manager->get('bi:alarm', ['class' => 'w-6 h-6', 'aria-label' => 'Alarm']);
```

Icons are prefixed with `bi`. You can also use the global helper:

```php
echo icon('bi:alarm');
echo icon('bi:house-fill', ['class' => 'text-blue-500']);
```

## Twig

```twig
{{ icon('bi:alarm') }}
{{ icon('bi:house-fill', {class: 'w-5 h-5'}) }}
```

## License

MIT. The Bootstrap Icons SVG files are licensed under the [MIT License](https://github.com/twbs/icons/blob/main/LICENSE).
