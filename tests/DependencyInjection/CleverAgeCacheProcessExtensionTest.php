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

use CleverAge\CacheProcessBundle\Attribute\AsCacheAdapter;
use CleverAge\CacheProcessBundle\DependencyInjection\CleverAgeCacheProcessExtension;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\CacheProcessBundle\Task\GetTask;
use CleverAge\CacheProcessBundle\Task\SetTask;
use CleverAge\CacheProcessBundle\Tests\Fixtures\AttributedAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\AttributeAutoconfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(CleverAgeCacheProcessExtension::class)]
#[UsesClass(AdapterRegistry::class)]
#[CoversClass(AsCacheAdapter::class)]
class CleverAgeCacheProcessExtensionTest extends TestCase
{
    public function testEveryServiceDefinitionFileIsLoaded(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessExtension())->load([], $container);

        $files = glob(\dirname(__DIR__, 2).'/config/services/*.yaml');
        self::assertIsArray($files);
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $config = Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS);
            self::assertIsArray($config);
            if (!isset($config['services'])) {
                continue;
            }
            self::assertIsArray($config['services']);
            foreach (array_keys($config['services']) as $id) {
                if ('_defaults' === $id) {
                    continue;
                }
                if (str_ends_with((string) $id, '\\')) {
                    // Resource: at least one service registered under the namespace prefix
                    $registered = array_filter(
                        array_keys($container->getDefinitions()),
                        static fn (string $definitionId): bool => str_starts_with($definitionId, (string) $id)
                    );
                    self::assertNotEmpty($registered, \sprintf('No service registered for the "%s" resource of %s', $id, basename($file)));
                    continue;
                }
                self::assertTrue($container->has((string) $id), \sprintf('Service "%s" of %s is not registered', $id, basename($file)));
            }
        }
    }

    public function testRegistryIsRegistered(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessExtension())->load([], $container);

        $definition = $container->getDefinition('cleverage_cache_process.registry.adapter');
        self::assertSame(AdapterRegistry::class, $definition->getClass());
        self::assertTrue($definition->isShared());
    }

    public function testAsCacheAdapterAttributeRegistersTheCode(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessExtension())->load([], $container);
        $container->setDefinition('app.attributed', (new Definition(AttributedAdapter::class))->setAutoconfigured(true));

        (new AttributeAutoconfigurationPass())->process($container);
        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        self::assertSame([['code' => 'app_attributed']], $container->getDefinition('app.attributed')->getTag('cleverage.cache.adapter'));
    }

    public function testRegistryCanBeAutowired(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessExtension())->load([], $container);

        $alias = $container->getAlias(AdapterRegistry::class);
        self::assertSame('cleverage_cache_process.registry.adapter', (string) $alias);
        self::assertFalse($alias->isPublic());

        // e.g. a custom cache task extending AbstractCacheTask, autowired by the application
        $container->register('app.service', AutowiredRegistryConsumer::class)->setAutowired(true)->setPublic(true);
        $container->compile(true);

        $service = $container->get('app.service');
        self::assertInstanceOf(AutowiredRegistryConsumer::class, $service);
        self::assertInstanceOf(AdapterRegistry::class, $service->registry);
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

final readonly class AutowiredRegistryConsumer
{
    public function __construct(public AdapterRegistry $registry)
    {
    }
}
