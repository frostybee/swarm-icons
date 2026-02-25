<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use Frostybee\SwarmIcons\Command\JsonBrowseCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class JsonBrowseCommandTest extends TestCase
{
    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new JsonBrowseCommand());

        $command = $application->find('json:browse');

        return new CommandTester($command);
    }

    public function test_command_is_registered(): void
    {
        $application = new Application();
        $application->add(new JsonBrowseCommand());

        $command = $application->find('json:browse');

        $this->assertSame('json:browse', $command->getName());
        $this->assertStringContainsString('Browse', $command->getDescription());
    }

    public function test_has_search_option(): void
    {
        $application = new Application();
        $application->add(new JsonBrowseCommand());

        $command = $application->find('json:browse');
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('search'));
        $this->assertSame('s', $definition->getOption('search')->getShortcut());
    }
}
