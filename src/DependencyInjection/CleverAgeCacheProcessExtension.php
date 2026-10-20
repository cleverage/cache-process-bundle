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

namespace CleverAge\CacheProcessBundle\DependencyInjection;

use CleverAge\CacheProcessBundle\Attribute\AsCacheAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see https://symfony.com/doc/current/bundles/extension.html
 *
 * @internal
 */
class CleverAgeCacheProcessExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $keys = array_keys(array_merge(...$configs));
        if ([] !== $keys) {
            trigger_deprecation(
                'cleverage/cache-process-bundle',
                '2.3',
                'Configuring "%s" is deprecated, the bundle has no configuration: the keys "%s" are ignored. They will be rejected in 3.0.',
                $this->getAlias(),
                implode('", "', $keys),
            );
        }
        $this->processConfiguration(new Configuration(), $configs);

        $this->findServices($container, __DIR__.'/../../config/services');

        $container->registerAttributeForAutoconfiguration(
            AsCacheAdapter::class,
            static function (ChildDefinition $definition, AsCacheAdapter $attribute): void {
                $definition->addTag('cleverage.cache.adapter', ['code' => $attribute->code]);
            }
        );
    }

    /**
     * Import the service definition files of a directory into the container.
     */
    protected function findServices(ContainerBuilder $container, string $path, string $extension = 'yaml'): void
    {
        $loader = new YamlFileLoader($container, new FileLocator($path));
        $loader->import('*.'.$extension, 'glob');
    }
}
