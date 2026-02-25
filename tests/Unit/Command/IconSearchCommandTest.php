<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use Frostybee\SwarmIcons\Command\IconSearchCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IconSearchCommandTest extends TestCase
{
    private string $fixturesPath;
    private string $jsonFixturePath;

    protected function setUp(): void
    {
        $this->fixturesPath = \dirname(__DIR__, 2) . '/Fixtures/icons';
        $this->jsonFixturePath = \dirname(__DIR__, 2) . '/Fixtures/test-collection.json';
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new IconSearchCommand());

        $command = $application->find('icon:search');

        return new CommandTester($command);
    }

    public function test_search_from_directory(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'custom',
            'term' => 'home',
            '--path' => $this->fixturesPath,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('home', $tester->getDisplay());
    }

    public function test_search_from_directory_no_match(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'custom',
            'term' => 'zzzznonexistent',
            '--path' => $this->fixturesPath,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('No icons matching', $tester->getDisplay());
    }

    public function test_search_fails_for_nonexistent_directory(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'custom',
            'term' => 'home',
            '--path' => '/nonexistent/path',
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function test_search_from_json_collection(): void
    {
        if (!file_exists($this->jsonFixturePath)) {
            $this->markTestSkipped('JSON fixture not found');
        }

        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'term' => 'home',
            '--json' => $this->jsonFixturePath,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('home', $tester->getDisplay());
    }

    public function test_search_fails_for_nonexistent_json(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            'prefix' => 'test',
            'term' => 'home',
            '--json' => '/nonexistent/file.json',
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }
}
