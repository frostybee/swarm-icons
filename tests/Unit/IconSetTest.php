<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\IconSetInterface;
use Frostybee\SwarmIcons\Tabler\TablerIconSet;
use Frostybee\SwarmIcons\Heroicons\HeroiconsIconSet;
use Frostybee\SwarmIcons\Lucide\LucideIconSet;
use Frostybee\SwarmIcons\Bootstrap\BootstrapIconSet;
use Frostybee\SwarmIcons\Phosphor\PhosphorIconSet;
use Frostybee\SwarmIcons\Simple\SimpleIconSet;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that all icon set classes correctly implement IconSetInterface
 * and return sensible metadata.
 */
class IconSetTest extends TestCase
{
    /**
     * @return array<string, array{class: class-string<IconSetInterface>, prefix: string}>
     */
    public static function iconSetProvider(): array
    {
        return [
            'tabler'     => ['class' => TablerIconSet::class,     'prefix' => 'tabler'],
            'heroicons'  => ['class' => HeroiconsIconSet::class,  'prefix' => 'heroicons'],
            'lucide'     => ['class' => LucideIconSet::class,     'prefix' => 'lucide'],
            'bootstrap'  => ['class' => BootstrapIconSet::class,  'prefix' => 'bi'],
            'phosphor'   => ['class' => PhosphorIconSet::class,   'prefix' => 'phosphor'],
            'simple'     => ['class' => SimpleIconSet::class,     'prefix' => 'simple'],
        ];
    }

    /**
     * @dataProvider iconSetProvider
     * @param class-string<IconSetInterface> $class
     */
    public function test_implements_icon_set_interface(string $class, string $prefix): void
    {
        $this->assertInstanceOf(IconSetInterface::class, new $class());
    }

    /**
     * @dataProvider iconSetProvider
     * @param class-string<IconSetInterface> $class
     */
    public function test_prefix_returns_non_empty_string(string $class, string $prefix): void
    {
        $this->assertSame($prefix, $class::prefix());
    }

    /**
     * @dataProvider iconSetProvider
     * @param class-string<IconSetInterface> $class
     */
    public function test_directory_returns_absolute_path(string $class, string $prefix): void
    {
        $dir = $class::directory();

        $this->assertNotEmpty($dir);
        // Must be an absolute path
        $this->assertMatchesRegularExpression('#^(/|[A-Za-z]:[/\\\\])#', $dir);
        // Must end with resources/svg
        $this->assertStringEndsWith('resources/svg', str_replace('\\', '/', $dir));
    }

    /**
     * @dataProvider iconSetProvider
     * @param class-string<IconSetInterface> $class
     */
    public function test_default_attributes_returns_array(string $class, string $prefix): void
    {
        $attrs = $class::defaultAttributes();

        $this->assertIsArray($attrs);
    }

    /**
     * @dataProvider iconSetProvider
     * @param class-string<IconSetInterface> $class
     */
    public function test_stroke_based_sets_have_expected_defaults(string $class, string $prefix): void
    {
        $strokeSets = ['tabler', 'heroicons', 'lucide'];

        if (!in_array($prefix, $strokeSets, true)) {
            $this->markTestSkipped("{$prefix} is not a stroke-based set");
        }

        $attrs = $class::defaultAttributes();
        $this->assertArrayHasKey('stroke', $attrs);
        $this->assertArrayHasKey('fill', $attrs);
        $this->assertSame('currentColor', $attrs['stroke']);
        $this->assertSame('none', $attrs['fill']);
    }

    /**
     * @dataProvider iconSetProvider
     * @param class-string<IconSetInterface> $class
     */
    public function test_fill_based_sets_have_expected_defaults(string $class, string $prefix): void
    {
        $fillSets = ['bi', 'phosphor', 'simple'];

        if (!in_array($prefix, $fillSets, true)) {
            $this->markTestSkipped("{$prefix} is not a fill-based set");
        }

        $attrs = $class::defaultAttributes();
        $this->assertArrayHasKey('fill', $attrs);
        $this->assertSame('currentColor', $attrs['fill']);
    }
}
