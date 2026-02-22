<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Exception;

/**
 * Thrown when an invalid cache key is provided.
 */
class CacheInvalidArgumentException extends CacheException implements \Psr\SimpleCache\InvalidArgumentException {}
