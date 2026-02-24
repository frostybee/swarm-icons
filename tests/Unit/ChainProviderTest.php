<?php

declare(strict_types=1);

namespace Frostybee\SwarmIcons\Tests\Unit;

use Frostybee\SwarmIcons\Icon;
use Frostybee\SwarmIcons\Provider\ChainProvider;
use Frostybee\SwarmIcons\Provider\IconProviderInterface;
use PHPUnit\Framework\TestCase;

class ChainProviderTest extends TestCase
{
    private function createProvider(array $icons): IconProviderInterface
    {
        return new class ($icons) implements IconProviderInterface {
            /** @param array<string, Icon> $icons */
            public function __construct(private readonly array $icons) {}

            public function get(string $name): ?Icon
            {
                return $this->icons[$name] ?? null;
            }

            public function has(string $name): bool
            {
                return isset($this->icons[$name]);
            }

            public function all(): iterable
            {
                return array_keys($this->icons);
            }
        };
    }

    private function makeIcon(string $content = '<path/>'): Icon
    {
        return new Icon($content, ['width' => '24', 'height' => '24']);
    }

    public function test_get_returns_from_first_provider(): void
    {
        $icon = $this->makeIcon('<path d="first"/>');
        $provider1 = $this->createProvider(['home' => $icon]);
        $provider2 = $this->createProvider(['home' => $this->makeIcon('<path d="second"/>')]);

        $chain = new ChainProvider([$provider1, $provider2]);

        $result = $chain->get('home');
        $this->assertNotNull($result);
        $this->assertStringContainsString('first', $result->getContent());
    }

    public function test_get_falls_through_to_second_provider(): void
    {
        $icon = $this->makeIcon('<path d="second"/>');
        $provider1 = $this->createProvider([]);
        $provider2 = $this->createProvider(['user' => $icon]);

        $chain = new ChainProvider([$provider1, $provider2]);

        $result = $chain->get('user');
        $this->assertNotNull($result);
        $this->assertStringContainsString('second', $result->getContent());
    }

    public function test_get_returns_null_when_no_provider_has_icon(): void
    {
        $provider1 = $this->createProvider([]);
        $provider2 = $this->createProvider([]);

        $chain = new ChainProvider([$provider1, $provider2]);

        $this->assertNull($chain->get('nonexistent'));
    }

    public function test_has_returns_true_if_any_provider_has_icon(): void
    {
        $provider1 = $this->createProvider([]);
        $provider2 = $this->createProvider(['user' => $this->makeIcon()]);

        $chain = new ChainProvider([$provider1, $provider2]);

        $this->assertTrue($chain->has('user'));
    }

    public function test_has_returns_false_when_no_provider_has_icon(): void
    {
        $provider1 = $this->createProvider([]);
        $provider2 = $this->createProvider([]);

        $chain = new ChainProvider([$provider1, $provider2]);

        $this->assertFalse($chain->has('nonexistent'));
    }

    public function test_all_deduplicates_across_providers(): void
    {
        $icon = $this->makeIcon();
        $provider1 = $this->createProvider(['home' => $icon, 'user' => $icon]);
        $provider2 = $this->createProvider(['home' => $icon, 'settings' => $icon]);

        $chain = new ChainProvider([$provider1, $provider2]);

        $all = iterator_to_array($chain->all());
        sort($all);

        $this->assertEquals(['home', 'settings', 'user'], $all);
    }

    public function test_all_merges_names_from_all_providers(): void
    {
        $icon = $this->makeIcon();
        $provider1 = $this->createProvider(['home' => $icon]);
        $provider2 = $this->createProvider(['user' => $icon]);

        $chain = new ChainProvider([$provider1, $provider2]);

        $all = iterator_to_array($chain->all());
        sort($all);

        $this->assertEquals(['home', 'user'], $all);
    }

    public function test_add_provider(): void
    {
        $chain = new ChainProvider();

        $this->assertEquals(0, $chain->count());

        $chain->addProvider($this->createProvider([]));

        $this->assertEquals(1, $chain->count());
    }

    public function test_add_provider_returns_self(): void
    {
        $chain = new ChainProvider();

        $result = $chain->addProvider($this->createProvider([]));

        $this->assertSame($chain, $result);
    }

    public function test_get_providers(): void
    {
        $p1 = $this->createProvider([]);
        $p2 = $this->createProvider([]);

        $chain = new ChainProvider([$p1, $p2]);

        $providers = $chain->getProviders();
        $this->assertCount(2, $providers);
        $this->assertSame($p1, $providers[0]);
        $this->assertSame($p2, $providers[1]);
    }

    public function test_count(): void
    {
        $chain = new ChainProvider([
            $this->createProvider([]),
            $this->createProvider([]),
            $this->createProvider([]),
        ]);

        $this->assertEquals(3, $chain->count());
    }

    public function test_empty_chain_get_returns_null(): void
    {
        $chain = new ChainProvider();

        $this->assertNull($chain->get('anything'));
    }

    public function test_empty_chain_has_returns_false(): void
    {
        $chain = new ChainProvider();

        $this->assertFalse($chain->has('anything'));
    }

    public function test_empty_chain_all_returns_empty(): void
    {
        $chain = new ChainProvider();

        $this->assertEquals([], iterator_to_array($chain->all()));
    }
}
