<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use FilesystemIterator;
use Frostybee\SwarmIcons\Command\InitCommand;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/swarm-icons-init-test-' . uniqid('', true);
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
        $application->add(new InitCommand());

        $command = $application->find('init');
        return new CommandTester($command);
    }

    public function test_creates_config_file(): void
    {
        $outputPath = $this->tempDir . '/swarm-icons.php';
        $tester = $this->createCommandTester();

        $tester->execute(['--output' => $outputPath]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertFileExists($outputPath);
        $this->assertStringContainsString('Configuration file created', $tester->getDisplay());
    }

    public function test_fails_if_file_exists(): void
    {
        $outputPath = $this->tempDir . '/swarm-icons.php';
        file_put_contents($outputPath, '<?php // existing');

        $tester = $this->createCommandTester();

        $tester->execute(['--output' => $outputPath]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function test_overwrites_with_force(): void
    {
        $outputPath = $this->tempDir . '/swarm-icons.php';
        file_put_contents($outputPath, '<?php // existing');

        $tester = $this->createCommandTester();

        $tester->execute(['--output' => $outputPath, '--force' => true]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('Configuration file created', $tester->getDisplay());

        // Content should be the template, not the old file
        $content = file_get_contents($outputPath);
        $this->assertStringContainsString('SwarmIconsConfig', $content);
    }

    public function test_custom_output_path(): void
    {
        $outputPath = $this->tempDir . '/custom-config.php';
        $tester = $this->createCommandTester();

        $tester->execute(['--output' => $outputPath]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertFileExists($outputPath);
    }

    public function test_template_contains_expected_content(): void
    {
        $outputPath = $this->tempDir . '/swarm-icons.php';
        $tester = $this->createCommandTester();

        $tester->execute(['--output' => $outputPath]);

        $content = file_get_contents($outputPath);
        $this->assertStringContainsString('SwarmIconsConfig::create()', $content);
        $this->assertStringContainsString('SwarmIcons::setManager', $content);
        $this->assertStringContainsString('cachePath', $content);
    }
}
