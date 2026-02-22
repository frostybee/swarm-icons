<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Cache;

use DateInterval;
use DateTime;
use FilesystemIterator;
use Frostybee\SwarmIcons\Exception\CacheException;
use Frostybee\SwarmIcons\Exception\CacheInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * PSR-16 compliant file-based cache implementation.
 *
 * Stores cached data as serialized PHP files with atomic writes.
 */
class FileCache implements CacheInterface
{
    private string $cacheDir;
    private int $defaultTtl;
    private int $dirPermissions;
    private int $filePermissions;

    /**
     * @param string $cacheDir Directory path for cache storage
     * @param int $defaultTtl Default TTL in seconds (0 = infinite)
     * @param int $dirPermissions Directory permissions (octal)
     * @param int $filePermissions File permissions (octal)
     */
    public function __construct(
        string $cacheDir,
        int $defaultTtl = 0,
        int $dirPermissions = 0o755,
        int $filePermissions = 0o644,
    ) {
        $this->cacheDir = rtrim($cacheDir, '/\\');
        $this->defaultTtl = $defaultTtl;
        $this->dirPermissions = $dirPermissions;
        $this->filePermissions = $filePermissions;

        $this->ensureDirectoryExists();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return $default;
        }

        $data = $this->readFile($filePath);

        if ($data === null) {
            return $default;
        }

        // Check expiration
        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        // PSR-16: negative TTL means the item should be deleted
        if (\is_int($ttl) && $ttl < 0) {
            $this->delete($key);
            return true;
        }

        $filePath = $this->getFilePath($key);
        $expiresAt = $this->calculateExpiresAt($ttl);

        $data = [
            'value' => $value,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ];

        return $this->writeFile($filePath, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return true;
        }

        return @unlink($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        if (!is_dir($this->cacheDir)) {
            return true;
        }

        return $this->deleteDirectory($this->cacheDir, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set((string) $key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return false;
        }

        $data = $this->readFile($filePath);

        if ($data === null) {
            return false;
        }

        // Check expiration
        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Get the file path for a cache key.
     */
    private function getFilePath(string $key): string
    {
        // Create a safe filename from the key
        $hash = hash('sha256', $key);
        $prefix = substr($hash, 0, 2);

        return $this->cacheDir . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . $hash . '.cache';
    }

    /**
     * Validate cache key format.
     *
     * @throws CacheException
     */
    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new CacheInvalidArgumentException('Cache key cannot be empty');
        }

        // PSR-16 specifies reserved characters
        if (preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new CacheInvalidArgumentException("Invalid cache key: {$key}");
        }
    }

    /**
     * Calculate expiration timestamp from TTL.
     */
    private function calculateExpiresAt(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        }

        if ($ttl instanceof DateInterval) {
            $now = new DateTime();
            $expires = $now->add($ttl);
            return $expires->getTimestamp();
        }

        if ($ttl === 0) {
            return null; // Never expires
        }

        return time() + $ttl;
    }

    /**
     * Read and unserialize a cache file.
     *
     * @return array<string, mixed>|null
     */
    private function readFile(string $filePath): ?array
    {
        $contents = @file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        try {
            $data = unserialize($contents, ['allowed_classes' => [\Frostybee\SwarmIcons\Icon::class]]);

            if (!\is_array($data) || !isset($data['value'])) {
                return null;
            }

            return $data;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Write data to cache file with atomic operation.
     *
     * @param array<string, mixed> $data
     */
    private function writeFile(string $filePath, array $data): bool
    {
        $dir = \dirname($filePath);

        if (!is_dir($dir)) {
            if (!@mkdir($dir, $this->dirPermissions, true) && !is_dir($dir)) {
                return false;
            }
        }

        $serialized = serialize($data);

        // Atomic write: write to temp file, then rename
        $tempFile = $filePath . '.' . uniqid('tmp', true);

        if (@file_put_contents($tempFile, $serialized) === false) {
            return false;
        }

        @chmod($tempFile, $this->filePermissions);

        if (!@rename($tempFile, $filePath)) {
            @unlink($tempFile);
            return false;
        }

        return true;
    }

    /**
     * Ensure cache directory exists.
     *
     * @throws CacheException
     */
    private function ensureDirectoryExists(): void
    {
        if (is_dir($this->cacheDir)) {
            return;
        }

        if (!@mkdir($this->cacheDir, $this->dirPermissions, true) && !is_dir($this->cacheDir)) {
            throw new CacheException("Failed to create cache directory: {$this->cacheDir}");
        }
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory(string $dir, bool $deleteSelf = true): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        if ($deleteSelf) {
            return @rmdir($dir);
        }

        return true;
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $files = 0;
        $size = 0;

        if (is_dir($this->cacheDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'cache') {
                    $files++;
                    $size += $file->getSize();
                }
            }
        }

        return [
            'files' => $files,
            'size' => $size,
        ];
    }
}
