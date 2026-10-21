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

namespace CleverAge\CacheProcessBundle\DependencyInjection\Compiler;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use CleverAge\CacheProcessBundle\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the `cleverage.cache.adapter` tagged services in the adapter registry: in a service locator indexed by the
 * `code` attribute of the tag (lazy), or, without `code` (deprecated), with an addAdapter() call (eager).
 *
 * A Symfony cache pool tagged with a `code` (a service implementing the AdapterInterface of the Cache component, but not
 * the one of the bundle) is wrapped in an Adapter with this code (`.cleverage_cache_process.adapter.<code>` service).
 *
 * @internal
 */
class RegisterAdaptersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('cleverage_cache_process.registry.adapter')) {
            return;
        }
        $definition = $container->findDefinition('cleverage_cache_process.registry.adapter');

        $services = [];
        $ids = [];
        foreach ($container->findTaggedServiceIds('cleverage.cache.adapter') as $id => $tags) {
            $codes = [];
            foreach ($tags as $attributes) {
                if (!\is_array($attributes) || !isset($attributes['code'])) {
                    continue;
                }
                if (!\is_string($attributes['code']) || '' === $attributes['code']) {
                    throw new InvalidArgumentException(\sprintf('The "code" attribute of the "cleverage.cache.adapter" tag of the "%s" service must be a non-empty string.', $id));
                }
                $codes[] = $attributes['code'];
            }
            if ([] === $codes) {
                // Deprecated: instantiated with the registry, addAdapter() triggers the deprecation
                $definition->addMethodCall('addAdapter', [new Reference($id), $id]);
                continue;
            }
            $isPool = $this->isPool($container, $id);
            foreach (array_unique($codes) as $code) {
                if (isset($ids[$code])) {
                    throw new InvalidArgumentException(\sprintf('The cache adapter code "%s" is used by the "%s" and "%s" services.', $code, $ids[$code], $id));
                }
                $ids[$code] = $id;
                if ($isPool) {
                    $container->setDefinition($adapterId = '.cleverage_cache_process.adapter.'.$code, new Definition(Adapter::class, [new Reference($id), $code]));
                    $services[$code] = new Reference($adapterId);
                } else {
                    $services[$code] = new Reference($id);
                }
            }
        }

        $definition->setArgument(0, ServiceLocatorTagPass::register($container, $services));
    }

    /**
     * Whether the service is a Symfony cache pool to wrap in an Adapter, from its class (the one of its parents for a
     * pool defined with "parent", as the FrameworkBundle pools). A service whose class cannot be loaded is registered as
     * is, and checked when it is fetched.
     */
    private function isPool(ContainerBuilder $container, string $id): bool
    {
        $definition = $container->getDefinition($id);
        $class = $definition->getClass();
        while (null === $class && $definition instanceof ChildDefinition) {
            $definition = $container->findDefinition($definition->getParent());
            $class = $definition->getClass();
        }
        $class = $container->getParameterBag()->resolveValue($class);
        if (!\is_string($class)) {
            return false;
        }
        $reflectionClass = $container->getReflectionClass($class, false);
        if (!$reflectionClass instanceof \ReflectionClass || $reflectionClass->implementsInterface(AdapterInterface::class)) {
            return false;
        }
        if ($reflectionClass->implementsInterface(\Symfony\Component\Cache\Adapter\AdapterInterface::class)) {
            return true;
        }

        throw new InvalidArgumentException(\sprintf('The "%s" service, tagged "cleverage.cache.adapter", must implement "%s" or "%s" (a Symfony cache pool), "%s" given.', $id, AdapterInterface::class, \Symfony\Component\Cache\Adapter\AdapterInterface::class, $class));
    }
}
