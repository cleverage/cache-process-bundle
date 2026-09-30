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

namespace CleverAge\CacheProcessBundle\Tests\Registry;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use CleverAge\CacheProcessBundle\Exception\MissingAdapterException;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(AdapterRegistry::class)]
#[UsesClass(Adapter::class)]
#[UsesClass(MissingAdapterException::class)]
class AdapterRegistryTest extends TestCase
{
    public function testGetAdapter(): void
    {
        $registry = new AdapterRegistry();
        $memory = new Adapter(new ArrayAdapter(), 'memory');
        $other = new Adapter(new ArrayAdapter(), 'other');
        $registry->addAdapter($memory, 'app.adapter.memory');
        $registry->addAdapter($other);

        self::assertSame($memory, $registry->getAdapter('memory'));
        self::assertSame($other, $registry->getAdapter('other'));
    }

    public function testMissingAdapter(): void
    {
        $this->expectException(MissingAdapterException::class);
        $this->expectExceptionMessage('Adapter missing is missing');
        (new AdapterRegistry())->getAdapter('missing');
    }

    public function testDuplicateCodeGivesTheServiceIds(): void
    {
        $registry = new AdapterRegistry();
        $registry->addAdapter(new Adapter(new ArrayAdapter(), 'memory'), 'app.adapter.memory');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Adapter memory is already defined by service "app.adapter.memory", cannot register service "app.adapter.memory_duplicate"');
        $registry->addAdapter(new Adapter(new ArrayAdapter(), 'memory'), 'app.adapter.memory_duplicate');
    }

    public function testDuplicateCodeWithoutServiceIds(): void
    {
        $registry = new AdapterRegistry();
        $registry->addAdapter(new Adapter(new ArrayAdapter(), 'memory'));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/^Adapter memory is already defined$/');
        $registry->addAdapter(new Adapter(new ArrayAdapter(), 'memory'), 'app.adapter.memory_duplicate');
    }
}
