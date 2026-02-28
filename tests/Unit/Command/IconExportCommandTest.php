<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use Frostybee\SwarmIcons\Command\IconExportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IconExportCommandTest extends TestCase
{
    private string $jsonFixturePath;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->jsonFixturePath = \dirname(__DIR__, 2) . '/Fixtures/test-collection.json';
        $this->tempDir = sys_get_temp_dir() . '/swarm-icons-export-test-' . uniqid();
        @mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new IconExportCommand());

        $command = $application->find('icon:export');

        return new CommandTester($command);
    }

    public function test_command_is_registered(): void
    {
        $application = new Application();
        $application->add(new IconExportCommand());

        $command = $application->find('icon:export');

        $this->assertSame('icon:export', $command->getName());
        $this->assertStringContainsString('Export', (string) $command->getDescription());
    }

    public function test_export_specific_icons(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'icons' => ['home', 'star'],
            '--json' => $this->jsonFixturePath,
            '--dest' => $this->tempDir,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertFileExists($this->tempDir . '/home.svg');
        $this->assertFileExists($this->tempDir . '/star.svg');
        $this->assertFileDoesNotExist($this->tempDir . '/user.svg');
    }

    public function test_export_all_icons(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            '--all' => true,
            '--json' => $this->jsonFixturePath,
            '--dest' => $this->tempDir,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        // 3 icons + 3 aliases = 6 files
        $this->assertFileExists($this->tempDir . '/home.svg');
        $this->assertFileExists($this->tempDir . '/user.svg');
        $this->assertFileExists($this->tempDir . '/star.svg');
        $this->assertFileExists($this->tempDir . '/house.svg');
        $this->assertFileExists($this->tempDir . '/person.svg');
        $this->assertFileExists($this->tempDir . '/chained-alias.svg');
    }

    public function test_export_skips_existing_without_overwrite(): void
    {
        // Pre-create a file with dummy content
        file_put_contents($this->tempDir . '/home.svg', 'existing content');

        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'icons' => ['home'],
            '--json' => $this->jsonFixturePath,
            '--dest' => $this->tempDir,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        // Content should not have changed
        $this->assertSame('existing content', file_get_contents($this->tempDir . '/home.svg'));
        $this->assertStringContainsString('skipped', $tester->getDisplay());
    }

    public function test_export_overwrites_with_flag(): void
    {
        // Pre-create a file with dummy content
        file_put_contents($this->tempDir . '/home.svg', 'existing content');

        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'icons' => ['home'],
            '--json' => $this->jsonFixturePath,
            '--dest' => $this->tempDir,
            '--overwrite' => true,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $content = file_get_contents($this->tempDir . '/home.svg');
        $this->assertNotSame('existing content', $content);
        $this->assertStringContainsString('<svg', (string) $content);
    }

    public function test_fails_for_nonexistent_json(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'icons' => ['home'],
            '--json' => '/nonexistent/file.json',
            '--dest' => $this->tempDir,
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('not found', $tester->getDisplay());
    }

    public function test_fails_when_no_icons_and_no_all_flag(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            '--json' => $this->jsonFixturePath,
            '--dest' => $this->tempDir,
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('--all', $tester->getDisplay());
    }

    public function test_exported_svg_contains_xmlns(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'icons' => ['home'],
            '--json' => $this->jsonFixturePath,
            '--dest' => $this->tempDir,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $content = file_get_contents($this->tempDir . '/home.svg');
        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', (string) $content);
    }

    public function test_warns_for_nonexistent_icon_name(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'icons' => ['nonexistent', 'home'],
            '--json' => $this->jsonFixturePath,
            '--dest' => $this->tempDir,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('not found', $tester->getDisplay());
        // The valid icon should still be exported
        $this->assertFileExists($this->tempDir . '/home.svg');
    }

    public function test_creates_destination_directory(): void
    {
        $nested = $this->tempDir . '/sub/nested';

        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'icons' => ['home'],
            '--json' => $this->jsonFixturePath,
            '--dest' => $nested,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertFileExists($nested . '/home.svg');
    }
}
