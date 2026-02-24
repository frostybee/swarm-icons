<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit\Twig;

use Frostybee\SwarmIcons\IconManager;
use Frostybee\SwarmIcons\Twig\SwarmIconsExtension;
use Frostybee\SwarmIcons\Twig\SwarmIconsRuntime;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class SwarmIconsExtensionTest extends TestCase
{
    private SwarmIconsExtension $extension;

    protected function setUp(): void
    {
        $manager = new IconManager();
        $runtime = new SwarmIconsRuntime($manager);
        $this->extension = new SwarmIconsExtension($runtime);
    }

    public function test_get_functions_returns_three_functions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(3, $functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);
    }

    public function test_function_names(): void
    {
        $functions = $this->extension->getFunctions();
        $names = array_map(fn(TwigFunction $f) => $f->getName(), $functions);

        $this->assertContains('icon', $names);
        $this->assertContains('icon_exists', $names);
        $this->assertContains('get_icon', $names);
    }

    public function test_icon_function_is_html_safe(): void
    {
        $functions = $this->extension->getFunctions();

        foreach ($functions as $function) {
            if ($function->getName() === 'icon') {
                $safe = $function->getSafe(new \Twig\Node\Expression\FunctionExpression('icon', new \Twig\Node\Nodes(), 0));
                $this->assertNotNull($safe);
                $this->assertContains('html', $safe);
                return;
            }
        }

        $this->fail('icon function not found');
    }
}
