<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for SwarmIcons.
 *
 * Registers the icon() function and other utilities for use in Twig templates.
 */
class SwarmIconsExtension extends AbstractExtension
{
    /**
     * @param SwarmIconsRuntime $runtime Runtime instance
     */
    public function __construct(
        private readonly SwarmIconsRuntime $runtime
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            // Main icon rendering function
            new TwigFunction(
                'icon',
                [$this->runtime, 'renderIcon'],
                ['is_safe' => ['html']]
            ),

            // Check if icon exists (for conditional rendering)
            new TwigFunction(
                'icon_exists',
                [$this->runtime, 'hasIcon']
            ),

            // Get icon object for advanced manipulation
            new TwigFunction(
                'get_icon',
                [$this->runtime, 'getIcon']
            ),
        ];
    }
}
