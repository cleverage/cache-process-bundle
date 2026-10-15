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

namespace CleverAge\CacheProcessBundle\Tests\DependencyInjection\Compiler;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use CleverAge\CacheProcessBundle\DependencyInjection\Compiler\RegisterAdaptersPass;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

#[CoversClass(RegisterAdaptersPass::class)]
#[UsesClass(AdapterRegistry::class)]
#[UsesClass(Adapter::class)]
class RegisterAdaptersPassTest extends TestCase
{
    public function testAdaptersWithACodeAreRegisteredInALocator(): void
    {
        $container = $this->createContainer(['app.adapter' => 'adapter', 'app.other' => 'other'], withCode: true);
        $container->compile(true);

        /** @var AdapterRegistry $registry */
        $registry = $container->get('cleverage_cache_process.registry.adapter');
        // Not instantiated with the registry
        self::assertFalse($container->initialized('app.adapter'));
        self::assertSame('adapter', $registry->getAdapter('adapter')->getCode());
        self::assertTrue($container->initialized('app.adapter'));
        self::assertFalse($container->initialized('app.other'));
        self::assertSame('other', $registry->getAdapter('other')->getCode());
    }

    public function testAdaptersWithoutCodeAreAddedToTheRegistry(): void
    {
        $container = $this->createContainer(['app.adapter' => 'adapter', 'app.other' => 'other']);
        $container->compile(true);

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            /** @var AdapterRegistry $registry */
            $registry = $container->get('cleverage_cache_process.registry.adapter');
        } finally {
            restore_error_handler();
        }

        self::assertCount(2, $deprecations);
        self::assertSame('adapter', $registry->getAdapter('adapter')->getCode());
        self::assertSame('other', $registry->getAdapter('other')->getCode());
    }

    public function testDuplicateCodeFailsTheCompilation(): void
    {
        $container = $this->createContainer(['app.adapter' => 'adapter', 'app.adapter_duplicate' => 'adapter'], withCode: true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The cache adapter code "adapter" is used by the "app.adapter" and "app.adapter_duplicate" services.');
        $container->compile(true);
    }

    public function testEmptyCodeFailsTheCompilation(): void
    {
        $container = $this->createContainer([]);
        $container->setDefinition('app.adapter', new Definition(Adapter::class))
            ->addTag('cleverage.cache.adapter', ['code' => '']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "code" attribute of the "cleverage.cache.adapter" tag of the "app.adapter" service must be a non-empty string.');
        $container->compile(true);
    }

    public function testWithoutRegistry(): void
    {
        $container = new ContainerBuilder();
        (new RegisterAdaptersPass())->process($container);

        self::assertFalse($container->has('cleverage_cache_process.registry.adapter'));
    }

    /**
     * @param array<string, string> $adapters Codes of the adapters, indexed by service id
     */
    private function createContainer(array $adapters, bool $withCode = false): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new RegisterAdaptersPass());
        $container->setDefinition('cleverage_cache_process.registry.adapter', new Definition(AdapterRegistry::class, [null]))
            ->setPublic(true);
        foreach ($adapters as $id => $code) {
            $container->setDefinition($id, new Definition(Adapter::class, [new Definition(ArrayAdapter::class), $code]))
                ->setPublic(true)
                ->addTag('cleverage.cache.adapter', $withCode ? ['code' => $code] : []);
        }

        return $container;
    }
}
