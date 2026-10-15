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
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function provideTasks(): iterable
    {
        yield 'get' => [GetTask::class, 'cache_get', 'cleverage_cache_process.task.get'];
        yield 'set' => [SetTask::class, 'cache_set', 'cleverage_cache_process.task.set'];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideTasks')]
    public function testTaskIsRegistered(string $class, string $code, string $deprecatedId): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessExtension())->load([], $container);

        // The class name is the service id
        $definition = $container->getDefinition($class);
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());
        self::assertEquals([new Reference('cleverage_cache_process.registry.adapter')], $definition->getArguments());
        // Referenced by its code in the process configurations (task: <code>)
        self::assertSame([['code' => $code]], $definition->getTag('cleverage.task'));
        // Public for the direct access from the service container, deprecated
        self::assertTrue($definition->isPublic());
        self::assertSame([['package' => 'cleverage/cache-process-bundle', 'version' => '2.3']], $definition->getTag('container.private'));

        // Former id, deprecated
        $alias = $container->getAlias($deprecatedId);
        self::assertSame($class, (string) $alias);
        self::assertFalse($alias->isPublic());
        self::assertTrue($alias->isDeprecated());
        self::assertSame(
            ['package' => 'cleverage/cache-process-bundle', 'version' => '2.3', 'message' => 'The "'.$deprecatedId.'" service alias is deprecated, use the "'.$code.'" task code or the class name of the task instead. It will be removed in 3.0.'],
            $alias->getDeprecation($deprecatedId)
        );
    }
}
