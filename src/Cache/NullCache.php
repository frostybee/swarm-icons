<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * No-op cache implementation for development/testing.
 *
 * Does not actually cache anything - all get() calls return default value.
 */
class NullCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $default;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return false;
    }
}
