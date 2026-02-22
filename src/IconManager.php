<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons;

use Frostybee\SwarmIcons\Exception\IconNotFoundException;
use Frostybee\SwarmIcons\Exception\InvalidIconNameException;
use Frostybee\SwarmIcons\Provider\IconProviderInterface;
use Throwable;

/**
 * Central icon registry and resolver.
 *
 * Manages icon providers, parses "prefix:name" format, and delegates rendering.
 */
class IconManager
{
    /** @var array<string, IconProviderInterface> Registered providers by prefix */
    private array $providers = [];

    private ?string $defaultPrefix = null;

    private ?string $fallbackIcon = null;

    /**
     * @param IconRenderer $renderer Icon renderer instance
     */
    public function __construct(
        private IconRenderer $renderer = new IconRenderer(),
    ) {}

    /**
     * Register an icon provider for a prefix.
     *
     * @param string $prefix Provider prefix (e.g., 'tabler', 'heroicons')
     * @param IconProviderInterface $provider Provider instance
     */
    public function register(string $prefix, IconProviderInterface $provider): self
    {
        $this->providers[$prefix] = $provider;

        return $this;
    }

    /**
     * Get a registered provider.
     */
    public function getProvider(string $prefix): ?IconProviderInterface
    {
        return $this->providers[$prefix] ?? null;
    }

    /**
     * Check if a provider is registered.
     */
    public function hasProvider(string $prefix): bool
    {
        return isset($this->providers[$prefix]);
    }

    /**
     * Get all registered provider prefixes.
     *
     * @return array<string>
     */
    public function getRegisteredPrefixes(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Set the default prefix.
     */
    public function setDefaultPrefix(?string $prefix): self
    {
        $this->defaultPrefix = $prefix;

        return $this;
    }

    /**
     * Get the default prefix.
     */
    public function getDefaultPrefix(): ?string
    {
        return $this->defaultPrefix;
    }

    /**
     * Set the fallback icon name.
     *
     * @param string|null $iconName Full icon name with prefix (e.g., 'tabler:question-mark')
     */
    public function setFallbackIcon(?string $iconName): self
    {
        $this->fallbackIcon = $iconName;

        return $this;
    }

    /**
     * Get the fallback icon name.
     */
    public function getFallbackIcon(): ?string
    {
        return $this->fallbackIcon;
    }

    /**
     * Get an icon by name.
     *
     * Supports formats:
     * - "prefix:name" → use specified prefix
     * - "name" → use default prefix
     *
     * @param string $name Icon name (with or without prefix)
     * @param array<string, bool|float|int|string|null> $attributes Additional attributes
     *
     * @throws IconNotFoundException
     * @throws InvalidIconNameException
     *
     * @return Icon Rendered icon
     */
    public function get(string $name, array $attributes = []): Icon
    {
        [$prefix, $iconName] = $this->parseName($name);

        $provider = $this->getProvider($prefix);

        if ($provider === null) {
            throw new IconNotFoundException("No provider registered for prefix: {$prefix}");
        }

        $icon = $provider->get($iconName);

        if ($icon === null) {
            // Try fallback icon
            if ($this->fallbackIcon !== null && $this->fallbackIcon !== $name) {
                try {
                    return $this->get($this->fallbackIcon, $attributes);
                } catch (Throwable) {
                    // Fallback failed, throw original error
                }
            }

            throw IconNotFoundException::forName($name);
        }

        // Render with attributes
        return $this->renderer->render($icon, $prefix, $attributes);
    }

    /**
     * Check if an icon exists.
     *
     * @param string $name Icon name (with or without prefix)
     */
    public function has(string $name): bool
    {
        try {
            [$prefix, $iconName] = $this->parseName($name);
        } catch (InvalidIconNameException) {
            return false;
        }

        $provider = $this->getProvider($prefix);

        if ($provider === null) {
            return false;
        }

        return $provider->has($iconName);
    }

    /**
     * Parse icon name into prefix and name components.
     *
     * @param string $name Full icon name
     *
     * @throws InvalidIconNameException
     *
     * @return array{0: string, 1: string} [prefix, name]
     */
    private function parseName(string $name): array
    {
        $name = trim($name);

        if (empty($name)) {
            throw InvalidIconNameException::forName($name);
        }

        // Check for "prefix:name" format
        if (str_contains($name, ':')) {
            $parts = explode(':', $name, 2);

            if (\count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
                throw InvalidIconNameException::forName($name);
            }

            return [$parts[0], $parts[1]];
        }

        // No prefix → use default
        if ($this->defaultPrefix === null) {
            throw new InvalidIconNameException(
                "Icon name '{$name}' has no prefix and no default prefix is set",
            );
        }

        return [$this->defaultPrefix, $name];
    }

    /**
     * Get the icon renderer.
     */
    public function getRenderer(): IconRenderer
    {
        return $this->renderer;
    }

    /**
     * Set the icon renderer.
     */
    public function setRenderer(IconRenderer $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Get all icons from a specific provider.
     *
     * @param string $prefix Provider prefix
     *
     * @return iterable<string> Icon names (without prefix)
     */
    public function all(string $prefix): iterable
    {
        $provider = $this->getProvider($prefix);

        if ($provider === null) {
            return [];
        }

        return $provider->all();
    }
}
