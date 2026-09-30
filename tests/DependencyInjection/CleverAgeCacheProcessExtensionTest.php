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

namespace CleverAge\CacheProcessBundle\Tests\DependencyInjection;

use CleverAge\CacheProcessBundle\DependencyInjection\CleverAgeCacheProcessExtension;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\CacheProcessBundle\Task\GetTask;
use CleverAge\CacheProcessBundle\Task\SetTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(CleverAgeCacheProcessExtension::class)]
class CleverAgeCacheProcessExtensionTest extends TestCase
{
    public function testRegistryIsRegistered(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessExtension())->load([], $container);

        $definition = $container->getDefinition('cleverage_cache_process.registry.adapter');
        self::assertSame(AdapterRegistry::class, $definition->getClass());
        self::assertTrue($definition->isShared());
    }

    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function provideTasks(): iterable
    {
        yield 'get' => ['cleverage_cache_process.task.get', GetTask::class];
        yield 'set' => ['cleverage_cache_process.task.set', SetTask::class];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideTasks')]
    public function testTaskIsRegistered(string $id, string $class): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessExtension())->load([], $container);

        $definition = $container->getDefinition($id);
        self::assertSame($class, $definition->getClass());
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());
        self::assertEquals([new Reference('cleverage_cache_process.registry.adapter')], $definition->getArguments());

        // Referenced as '@<class>' in process configurations
        $alias = $container->getAlias($class);
        self::assertSame($id, (string) $alias);
        self::assertTrue($alias->isPublic());
    }
}
