<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use Frostybee\SwarmIcons\Cache\FileCache;
use Frostybee\SwarmIcons\Command\CacheClearCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CacheClearCommandTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/swarm-icons-clear-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
            @rmdir($this->cacheDir);
        }
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new CacheClearCommand());

        $command = $application->find('cache:clear');
        return new CommandTester($command);
    }

    public function test_clear_nonexistent_directory(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--path' => $this->cacheDir]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function test_clear_populated_cache(): void
    {
        // Populate cache
        $cache = new FileCache($this->cacheDir);
        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');

        $tester = $this->createCommandTester();

        $tester->execute(['--path' => $this->cacheDir]);

        $this->assertEquals(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Cache cleared', $display);
        $this->assertStringContainsString('2 cached files', $display);
    }

    public function test_displays_stats(): void
    {
        $cache = new FileCache($this->cacheDir);
        $cache->set('key1', str_repeat('x', 1000));

        $tester = $this->createCommandTester();

        $tester->execute(['--path' => $this->cacheDir]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('1 cached files', $display);
        // Should show size in B or KB
        $this->assertMatchesRegularExpression('/\d+(\.\d+)?\s+(B|KB|MB)/', $display);
    }
}
