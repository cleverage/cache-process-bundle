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
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the `cleverage.cache.adapter` tagged services in the adapter registry: in a service locator indexed by the
 * `code` attribute of the tag (lazy), or, without `code` (deprecated), with an addAdapter() call (eager).
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
            foreach (array_unique($codes) as $code) {
                if (isset($ids[$code])) {
                    throw new InvalidArgumentException(\sprintf('The cache adapter code "%s" is used by the "%s" and "%s" services.', $code, $ids[$code], $id));
                }
                $ids[$code] = $id;
                $services[$code] = new Reference($id);
            }
        }

        $definition->setArgument(0, ServiceLocatorTagPass::register($container, $services));
    }
}
