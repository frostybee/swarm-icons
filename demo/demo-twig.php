<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Frostybee\SwarmIcons\SwarmIconsConfig;
use Frostybee\SwarmIcons\Twig\SwarmIconsExtension;
use Frostybee\SwarmIcons\Twig\SwarmIconsRuntime;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Setup IconManager
$manager = SwarmIconsConfig::create()
    ->addIconifySet('heroicons')
    ->addIconifySet('lucide')
    ->defaultPrefix('heroicons')
    ->defaultAttributes(['class' => 'icon'])
    ->cachePath(__DIR__ . '/cache/icons')
    ->build();

// Setup Twig
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader, [
    'cache' => false, // Disable cache for demo
    'debug' => true,
]);

// Register SwarmIcons extension
$twig->addExtension(new SwarmIconsExtension(
    new SwarmIconsRuntime($manager, silentOnMissing: true)
));

// Render template
echo $twig->render('demo.twig', [
    'title' => 'SwarmIcons Twig Integration Demo',
    'icons' => [
        ['name' => 'heroicons:home', 'label' => 'Home'],
        ['name' => 'heroicons:user', 'label' => 'User'],
        ['name' => 'heroicons:cog-6-tooth', 'label' => 'Settings'],
        ['name' => 'lucide:heart', 'label' => 'Heart'],
        ['name' => 'lucide:star', 'label' => 'Star'],
    ],
]);
