<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Command;

use Frostybee\SwarmIcons\Command\JsonUpdateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class JsonUpdateCommandTest extends TestCase
{
    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new JsonUpdateCommand());

        $command = $application->find('json:update');

        return new CommandTester($command);
    }

    public function test_command_is_registered(): void
    {
        $application = new Application();
        $application->add(new JsonUpdateCommand());

        $command = $application->find('json:update');

        $this->assertSame('json:update', $command->getName());
        $this->assertStringContainsString('update', (string) $command->getDescription());
    }

    public function test_has_dry_run_option(): void
    {
        $application = new Application();
        $application->add(new JsonUpdateCommand());

        $command = $application->find('json:update');
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertFalse($definition->getOption('dry-run')->acceptValue());
    }

    public function test_has_dest_option(): void
    {
        $application = new Application();
        $application->add(new JsonUpdateCommand());

        $command = $application->find('json:update');
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dest'));
        $this->assertSame('d', $definition->getOption('dest')->getShortcut());
    }

    public function test_accepts_sets_argument(): void
    {
        $application = new Application();
        $application->add(new JsonUpdateCommand());

        $command = $application->find('json:update');
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('sets'));
        $this->assertTrue($definition->getArgument('sets')->isArray());
    }
}
