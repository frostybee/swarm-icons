<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Provider;

use Frostybee\SwarmIcons\Icon;

/**
 * Composite provider that tries multiple providers in sequence.
 *
 * Useful for fallback scenarios (e.g., local files first, then Iconify API).
 */
class ChainProvider implements IconProviderInterface
{
    /** @var array<IconProviderInterface> */
    private array $providers;

    /**
     * @param array<IconProviderInterface> $providers Providers to try in order
     */
    public function __construct(array $providers = [])
    {
        $this->providers = array_values($providers);
    }

    /**
     * Add a provider to the chain.
     *
     * @param IconProviderInterface $provider
     * @return self
     */
    public function addProvider(IconProviderInterface $provider): self
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): ?Icon
    {
        foreach ($this->providers as $provider) {
            $icon = $provider->get($name);

            if ($icon !== null) {
                return $icon;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        $allIcons = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->all() as $iconName) {
                $allIcons[$iconName] = true; // Use array key to deduplicate
            }
        }

        return array_keys($allIcons);
    }

    /**
     * Get all providers in the chain.
     *
     * @return array<IconProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get the number of providers in the chain.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->providers);
    }
}
