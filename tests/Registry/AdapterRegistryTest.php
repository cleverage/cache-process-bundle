<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/CacheProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\CacheProcessBundle\Tests\Registry;

use CleverAge\CacheProcessBundle\Adapter\AdapterInterface;
use CleverAge\CacheProcessBundle\Exception\MissingAdapterException;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(AdapterRegistry::class)]
#[UsesClass(MissingAdapterException::class)]
class AdapterRegistryTest extends TestCase
{
    /** @var list<string> Codes of the adapters instantiated by the locator */
    private array $instantiated = [];

    public function testAdaptersAreFetchedLazilyFromTheLocator(): void
    {
        $registry = new AdapterRegistry($this->createLocator(['adapter' => 'adapter', 'other' => 'other']));

        self::assertTrue($registry->hasAdapter('adapter'));
        self::assertFalse($registry->hasAdapter('missing'));
        self::assertSame([], $this->instantiated);

        $adapter = $registry->getAdapter('adapter');
        self::assertSame('adapter', $adapter->getCode());
        self::assertSame($adapter, $registry->getAdapter('adapter'));
        self::assertSame(['adapter'], $this->instantiated);

        self::assertSame(['adapter', 'other'], array_keys($registry->getAdapters()));
        self::assertSame(['adapter', 'other'], $this->instantiated);
    }

    public function testMissingAdapter(): void
    {
        $this->expectException(MissingAdapterException::class);
        $this->expectExceptionMessage('Adapter missing is missing');
        (new AdapterRegistry($this->createLocator([])))->getAdapter('missing');
    }

    public function testMissingAdapterWithoutLocator(): void
    {
        $this->expectException(MissingAdapterException::class);
        (new AdapterRegistry())->getAdapter('missing');
    }

    public function testCodeOfTheTagMustBeTheCodeOfTheAdapter(): void
    {
        $registry = new AdapterRegistry($this->createLocator(['adapter' => 'other']));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/is registered with the code "adapter" but its getCode\(\) method returns "other"/');
        $registry->getAdapter('adapter');
    }

    public function testServiceMustBeAnAdapter(): void
    {
        $registry = new AdapterRegistry(new ServiceLocator(['adapter' => static fn (): \stdClass => new \stdClass()]));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The service of the cache adapter "adapter" must implement "'.AdapterInterface::class.'".');
        $registry->getAdapter('adapter');
    }

    public function testAddAdapterIsDeprecated(): void
    {
        $registry = new AdapterRegistry($this->createLocator(['adapter' => 'adapter']));
        $other = $this->createAdapter('other');

        $deprecations = $this->collectDeprecations(static fn () => $registry->addAdapter($other, 'app.other'));

        self::assertSame(
            ['Since cleverage/cache-process-bundle 2.3: Registering the cache adapter "other" ('.$other::class.') with "'.AdapterRegistry::class.'::addAdapter()" is deprecated, tag its service with a "code" attribute ({ name: cleverage.cache.adapter, code: other }) or use the #[AsCacheAdapter] attribute instead, so that it is only instantiated when used. It will be required in 3.0.'],
            $deprecations
        );
        self::assertSame($other, $registry->getAdapter('other'));
        self::assertEqualsCanonicalizing(['adapter', 'other'], array_keys($registry->getAdapters()));
    }

    public function testAddAdapterWithTheCodeOfALocatorAdapter(): void
    {
        $registry = new AdapterRegistry($this->createLocator(['adapter' => 'adapter']));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/^Adapter adapter is already defined$/');
        $this->collectDeprecations(fn () => $registry->addAdapter($this->createAdapter('adapter'), 'app.adapter_duplicate'));
    }

    public function testDuplicateCodeGivesTheServiceIds(): void
    {
        $registry = new AdapterRegistry();
        $this->collectDeprecations(fn () => $registry->addAdapter($this->createAdapter('adapter'), 'app.adapter'));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Adapter adapter is already defined by service "app.adapter", cannot register service "app.adapter_duplicate"');
        $this->collectDeprecations(fn () => $registry->addAdapter($this->createAdapter('adapter'), 'app.adapter_duplicate'));
    }

    public function testDuplicateCodeWithoutServiceIds(): void
    {
        $registry = new AdapterRegistry();
        $this->collectDeprecations(fn () => $registry->addAdapter($this->createAdapter('adapter')));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/^Adapter adapter is already defined$/');
        $this->collectDeprecations(fn () => $registry->addAdapter($this->createAdapter('adapter'), 'app.adapter_duplicate'));
    }

    /**
     * @param array<string, string> $codes Code returned by each adapter, indexed by the code of the locator
     *
     * @return ServiceLocator<mixed>
     */
    private function createLocator(array $codes): ServiceLocator
    {
        $factories = [];
        foreach ($codes as $code => $adapterCode) {
            $factories[$code] = function () use ($code, $adapterCode): AdapterInterface {
                $this->instantiated[] = $code;

                return $this->createAdapter($adapterCode);
            };
        }

        return new ServiceLocator($factories);
    }

    private function createAdapter(string $code): AdapterInterface
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getCode')->willReturn($code);

        return $adapter;
    }

    /**
     * @return list<string>
     */
    private function collectDeprecations(callable $callable): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callable();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
