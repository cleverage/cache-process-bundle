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

namespace CleverAge\CacheProcessBundle\Tests;

use CleverAge\CacheProcessBundle\CleverAgeCacheProcessBundle;
use CleverAge\CacheProcessBundle\DependencyInjection\Compiler\RegisterAdaptersPass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(CleverAgeCacheProcessBundle::class)]
class CleverAgeCacheProcessBundleTest extends TestCase
{
    public function testPathIsTheBundleRoot(): void
    {
        $path = (new CleverAgeCacheProcessBundle())->getPath();

        self::assertSame(\dirname(__DIR__), $path);
        self::assertDirectoryExists($path.'/config/services');
    }

    public function testAdaptersPassIsRegistered(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeCacheProcessBundle())->build($container);

        $passes = array_filter(
            $container->getCompilerPassConfig()->getBeforeOptimizationPasses(),
            static fn (object $pass): bool => $pass instanceof RegisterAdaptersPass
        );
        self::assertCount(1, $passes);
    }
}
