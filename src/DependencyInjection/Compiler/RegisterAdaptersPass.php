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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds the tagged cache adapters to the adapter registry, with their service id.
 */
class RegisterAdaptersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('cleverage_cache_process.registry.adapter')) {
            return;
        }

        $definition = $container->findDefinition('cleverage_cache_process.registry.adapter');
        foreach (array_keys($container->findTaggedServiceIds('cleverage.cache.adapter')) as $id) {
            $definition->addMethodCall('addAdapter', [new Reference($id), $id]);
        }
    }
}
