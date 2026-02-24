<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use Frostybee\SwarmIcons\Command\IconListCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IconListCommandTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = \dirname(__DIR__, 2) . '/Fixtures/icons';
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new IconListCommand());

        $command = $application->find('icon:list');
        return new CommandTester($command);
    }

    public function test_list_from_directory(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--path' => $this->fixturesPath]);

        $this->assertEquals(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('home', $display);
    }

    public function test_list_from_directory_with_search(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--path' => $this->fixturesPath, '--search' => 'home']);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('home', $tester->getDisplay());
    }

    public function test_list_from_directory_search_no_match(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--path' => $this->fixturesPath, '--search' => 'zzzznonexistent']);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('No icons matching', $tester->getDisplay());
    }

    public function test_fails_for_nonexistent_directory(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--path' => '/nonexistent/path']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function test_fails_without_options(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('Provide --path', $tester->getDisplay());
    }
}
