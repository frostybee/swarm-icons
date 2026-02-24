<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use FilesystemIterator;
use Frostybee\SwarmIcons\Command\ManifestGenerateCommand;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ManifestGenerateCommandTest extends TestCase
{
    private string $fixturesPath;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->fixturesPath = \dirname(__DIR__, 2) . '/Fixtures/icons';
        $this->tempDir = sys_get_temp_dir() . '/swarm-icons-manifest-cmd-test-' . uniqid('', true);
        @mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
            @rmdir($this->tempDir);
        }
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new ManifestGenerateCommand());

        $command = $application->find('manifest:generate');
        return new CommandTester($command);
    }

    public function test_generate_manifest(): void
    {
        $output = $this->tempDir . '/icons.json';
        $tester = $this->createCommandTester();

        $tester->execute([
            '--path' => $this->fixturesPath,
            '--prefix' => 'custom',
            '--output' => $output,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertFileExists($output);
        $this->assertStringContainsString('Manifest generated', $tester->getDisplay());

        $content = json_decode(file_get_contents($output), true);
        $this->assertArrayHasKey('custom', $content);
    }

    public function test_fails_without_path(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('--path', $tester->getDisplay());
    }

    public function test_fails_with_nonexistent_directory(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--path' => '/nonexistent/path']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }
}
