<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use Frostybee\SwarmIcons\Command\CacheWarmCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CacheWarmCommandTest extends TestCase
{
    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new CacheWarmCommand());

        $command = $application->find('cache:warm');
        return new CommandTester($command);
    }

    public function test_fails_without_prefix(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--icons' => 'home,user']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('--prefix', $tester->getDisplay());
    }

    public function test_fails_without_icons(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--prefix' => 'tabler']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('--icons', $tester->getDisplay());
    }

    public function test_fails_with_empty_prefix(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--prefix' => '', '--icons' => 'home']);

        $this->assertEquals(1, $tester->getStatusCode());
    }

    public function test_fails_with_empty_icons(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--prefix' => 'tabler', '--icons' => '']);

        $this->assertEquals(1, $tester->getStatusCode());
    }
}
