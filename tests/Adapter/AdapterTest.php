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

namespace CleverAge\CacheProcessBundle\Tests\Adapter;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(Adapter::class)]
class AdapterTest extends TestCase
{
    private ArrayAdapter $pool;

    private Adapter $adapter;

    protected function setUp(): void
    {
        $this->pool = new ArrayAdapter();
        $this->adapter = new Adapter($this->pool, 'memory');
    }

    public function testGetCode(): void
    {
        self::assertSame('memory', $this->adapter->getCode());
    }

    public function testSaveAndGetItem(): void
    {
        self::assertFalse($this->adapter->getItem('key1')->isHit());

        self::assertTrue($this->adapter->save($this->adapter->getItem('key1')->set('value1')));

        self::assertTrue($this->adapter->hasItem('key1'));
        self::assertSame('value1', $this->adapter->getItem('key1')->get());
        // Stored in the decorated pool
        self::assertSame('value1', $this->pool->getItem('key1')->get());
    }

    public function testGetItems(): void
    {
        $this->pool->save($this->pool->getItem('key1')->set('value1'));

        $values = [];
        foreach ($this->adapter->getItems(['key1', 'key2']) as $key => $item) {
            $values[$key] = $item->get();
        }

        self::assertSame(['key1' => 'value1', 'key2' => null], $values);
    }

    public function testSaveDeferredAndCommit(): void
    {
        self::assertTrue($this->adapter->saveDeferred($this->adapter->getItem('key1')->set('value1')));
        self::assertTrue($this->adapter->commit());

        self::assertSame('value1', $this->pool->getItem('key1')->get());
    }

    public function testDeleteItems(): void
    {
        foreach (['key1', 'key2', 'key3'] as $key) {
            $this->pool->save($this->pool->getItem($key)->set($key));
        }

        self::assertTrue($this->adapter->deleteItem('key1'));
        self::assertTrue($this->adapter->deleteItems(['key2']));

        self::assertFalse($this->pool->hasItem('key1'));
        self::assertFalse($this->pool->hasItem('key2'));
        self::assertTrue($this->pool->hasItem('key3'));
    }

    public function testClear(): void
    {
        $this->pool->save($this->pool->getItem('key1')->set('value1'));

        self::assertTrue($this->adapter->clear());

        self::assertFalse($this->pool->hasItem('key1'));
    }
}
