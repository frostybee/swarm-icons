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

    /** @var array<string, string> User-defined aliases mapping short names to full prefix:name */
    private array $aliases = [];

    private ?string $defaultPrefix = null;

    private ?string $fallbackIcon = null;

    /** @var array<string, string> Per-prefix fallback icons (prefix → full icon name) */
    private array $prefixFallbackIcons = [];

    private bool $ignoreNotFound = false;

    private ?SpriteSheet $spriteSheet = null;

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
     * Set a per-prefix fallback icon.
     *
     * @param string $prefix Provider prefix
     * @param string $iconName Full icon name with prefix (e.g., 'tabler:help')
     */
    public function setFallbackIconForPrefix(string $prefix, string $iconName): self
    {
        $this->prefixFallbackIcons[$prefix] = $iconName;

        return $this;
    }

    /**
     * Get the per-prefix fallback icon name.
     */
    public function getFallbackIconForPrefix(string $prefix): ?string
    {
        return $this->prefixFallbackIcons[$prefix] ?? null;
    }

    /**
     * Register a user-defined alias.
     *
     * Aliases are resolved before prefix parsing, mapping a short name
     * to a full "prefix:name" icon reference.
     *
     * @param string $alias Short alias name (e.g., 'check')
     * @param string $target Full icon name (e.g., 'heroicons:check-circle')
     */
    public function setAlias(string $alias, string $target): void
    {
        $this->aliases[$alias] = $target;
    }

    /**
     * Get all registered aliases.
     *
     * @return array<string, string>
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Set whether to ignore not-found errors.
     *
     * When enabled, returns an empty Icon instead of throwing IconNotFoundException.
     */
    public function setIgnoreNotFound(bool $ignore): void
    {
        $this->ignoreNotFound = $ignore;
    }

    /**
     * Get the ignore-not-found setting.
     */
    public function getIgnoreNotFound(): bool
    {
        return $this->ignoreNotFound;
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
     * @param bool $resolvingFallback Whether we are already resolving a fallback icon
     *
     * @throws IconNotFoundException
     * @throws InvalidIconNameException
     *
     * @return Icon Rendered icon
     */
    public function get(string $name, array $attributes = [], bool $resolvingFallback = false): Icon
    {
        // Resolve user-defined aliases before prefix parsing
        $name = $this->resolveAlias($name);

        [$prefix, $iconName] = $this->parseName($name);

        $provider = $this->getProvider($prefix);

        if ($provider === null) {
            if ($this->ignoreNotFound) {
                return new Icon('');
            }

            throw new IconNotFoundException("No provider registered for prefix: {$prefix}");
        }

        $icon = $provider->get($iconName);

        if ($icon === null) {
            // Try fallback icon (only if not already resolving a fallback)
            // Resolution order: per-prefix fallback → global fallback
            if (!$resolvingFallback) {
                $effectiveFallback = $this->prefixFallbackIcons[$prefix] ?? $this->fallbackIcon;

                if ($effectiveFallback !== null) {
                    try {
                        return $this->get($effectiveFallback, $attributes, true);
                    } catch (Throwable) {
                        // Fallback failed, throw original error
                    }
                }
            }

            if ($this->ignoreNotFound) {
                return new Icon('');
            }

            throw IconNotFoundException::forName($name);
        }

        // Render with attributes (pass iconName for suffix matching)
        return $this->renderer->render($icon, $prefix, $attributes, $iconName);
    }

    /**
     * Check if an icon exists.
     *
     * @param string $name Icon name (with or without prefix)
     */
    public function has(string $name): bool
    {
        // Resolve user-defined aliases before prefix parsing
        $name = $this->resolveAlias($name);

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
     * Resolve a user-defined alias to its target icon name.
     */
    private function resolveAlias(string $name): string
    {
        return $this->aliases[trim($name)] ?? $name;
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
     * Create an icon stack for layering multiple icons.
     *
     * @param string ...$names Icon names to push as initial layers
     */
    public function stack(string ...$names): IconStack
    {
        $stack = new IconStack();

        foreach ($names as $name) {
            $stack = $stack->push($this->get($name));
        }

        return $stack;
    }

    /**
     * Get or create the shared sprite sheet instance.
     *
     * Returns the same SpriteSheet on every call, so all icons
     * registered via use() end up in a single sprite sheet.
     */
    public function spriteSheet(): SpriteSheet
    {
        if ($this->spriteSheet === null) {
            $this->spriteSheet = new SpriteSheet($this);
        }

        return $this->spriteSheet;
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
