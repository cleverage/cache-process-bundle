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

use CleverAge\CacheProcessBundle\DependencyInjection\CleverAgeCacheProcessExtension;
use CleverAge\CacheProcessBundle\DependencyInjection\Compiler\RegisterAdaptersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final since 2.3
 */
class CleverAgeCacheProcessBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into registry.
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterAdaptersPass());
    }

    /**
     * The alias of the extension, "cleverage_cache_process", is not the one guessed from the bundle name.
     */
    #[\Override]
    public function getContainerExtension(): ExtensionInterface
    {
        if (!$this->extension instanceof ExtensionInterface) {
            $this->extension = new CleverAgeCacheProcessExtension();
        }

        return $this->extension;
    }

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
