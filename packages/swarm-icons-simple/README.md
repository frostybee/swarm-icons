# swarm-icons-simple

Simple Icons for the [frostybee/swarm-icons](https://github.com/swarm-icons/swarm-icons) PHP library. Provides ~3,400 SVG icons from the [Simple Icons](https://simpleicons.org) set, covering popular brands, tools, and platforms with a consistent monochrome style.

## Installation

```bash
composer require frostybee/swarm-icons-simple
```

This will pull in `frostybee/swarm-icons` automatically.

## Usage

```php
use Frostybee\SwarmIcons\SwarmIconsConfig;

$manager = SwarmIconsConfig::create()
    ->addIconSet('simple')
    ->build();

echo $manager->get('simple:github');
echo $manager->get('simple:github', ['class' => 'w-6 h-6', 'aria-label' => 'GitHub']);
```

Icons are prefixed with `simple`. You can also use the global helper:

```php
echo icon('simple:github');
echo icon('simple:laravel', ['class' => 'w-5 h-5']);
```

## Twig

```twig
{{ icon('simple:github') }}
{{ icon('simple:laravel', {class: 'w-6 h-6'}) }}
```

## License

MIT. The Simple Icons SVG files are licensed under [CC0 1.0 Universal](https://github.com/simple-icons/simple-icons/blob/develop/LICENSE.md).
