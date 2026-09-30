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

#[CoversClass(RegisterAdaptersPass::class)]
#[UsesClass(AdapterRegistry::class)]
#[UsesClass(Adapter::class)]
class RegisterAdaptersPassTest extends TestCase
{
    public function testAdaptersAreRegistered(): void
    {
        $container = $this->createContainer(['app.adapter.memory' => 'memory', 'app.adapter.other' => 'other']);
        $container->compile(true);

        /** @var AdapterRegistry $registry */
        $registry = $container->get('cleverage_cache_process.registry.adapter');
        self::assertSame('memory', $registry->getAdapter('memory')->getCode());
        self::assertSame('other', $registry->getAdapter('other')->getCode());
    }

    public function testDuplicateCodeGivesTheServiceIds(): void
    {
        $container = $this->createContainer(['app.adapter.memory' => 'memory', 'app.adapter.memory_duplicate' => 'memory']);
        $container->compile(true);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Adapter memory is already defined by service "app.adapter.memory", cannot register service "app.adapter.memory_duplicate"');
        $container->get('cleverage_cache_process.registry.adapter');
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
    private function createContainer(array $adapters): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new RegisterAdaptersPass());
        $container->setDefinition('cleverage_cache_process.registry.adapter', new Definition(AdapterRegistry::class))
            ->setPublic(true);
        foreach ($adapters as $id => $code) {
            $container->setDefinition($id, new Definition(Adapter::class, [new Definition(ArrayAdapter::class), $code]))
                ->addTag('cleverage.cache.adapter');
        }

        return $container;
    }
}
