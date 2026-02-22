<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Exception\ProviderException;
use Frostybee\SwarmIcons\Provider\DirectoryProvider;
use PHPUnit\Framework\TestCase;

class DirectoryProviderTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../Fixtures/icons';
    }

    public function test_constructor_with_valid_directory(): void
    {
        $provider = new DirectoryProvider($this->fixturesPath);

        $this->assertInstanceOf(DirectoryProvider::class, $provider);
    }

    public function test_constructor_with_invalid_directory_throws_exception(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Directory does not exist');

        new DirectoryProvider('/nonexistent/directory');
    }

    public function test_get_returns_icon(): void
    {
        $provider = new DirectoryProvider($this->fixturesPath);

        $icon = $provider->get('home');

        $this->assertNotNull($icon);
        $this->assertStringContainsString('path', $icon->getContent());
    }

    public function test_get_returns_null_for_nonexistent_icon(): void
    {
        $provider = new DirectoryProvider($this->fixturesPath);

        $icon = $provider->get('nonexistent');

        $this->assertNull($icon);
    }

    public function test_has_returns_true_for_existing_icon(): void
    {
        $provider = new DirectoryProvider($this->fixturesPath);

        $this->assertTrue($provider->has('home'));
        $this->assertTrue($provider->has('user'));
    }

    public function test_has_returns_false_for_nonexistent_icon(): void
    {
        $provider = new DirectoryProvider($this->fixturesPath);

        $this->assertFalse($provider->has('nonexistent'));
    }

    public function test_all_returns_icon_names(): void
    {
        $provider = new DirectoryProvider($this->fixturesPath);

        $icons = iterator_to_array($provider->all());

        $this->assertContains('home', $icons);
        $this->assertContains('user', $icons);
    }

    public function test_path_traversal_protection(): void
    {
        $provider = new DirectoryProvider($this->fixturesPath);

        $icon = $provider->get('../../../etc/passwd');

        $this->assertNull($icon);
    }
}
