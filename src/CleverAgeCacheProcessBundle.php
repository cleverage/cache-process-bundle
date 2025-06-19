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

namespace CleverAge\CacheProcessBundle;

use CleverAge\ProcessBundle\DependencyInjection\Compiler\RegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CleverAgeCacheProcessBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into registry.
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new RegistryCompilerPass(
                'cleverage_cache_process.registry.adapter',
                'cleverage.cache.adapter',
                'addAdapter'
            )
        );
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
