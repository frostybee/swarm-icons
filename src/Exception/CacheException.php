<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Exception;

/**
 * Thrown when cache operations fail.
 */
class CacheException extends SwarmIconsException implements \Psr\SimpleCache\CacheException {}
