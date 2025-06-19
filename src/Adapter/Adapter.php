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

namespace CleverAge\CacheProcessBundle\Adapter;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\CacheItem;

class Adapter implements AdapterInterface
{
    public function __construct(
        private readonly \Symfony\Component\Cache\Adapter\AdapterInterface $adapter,
        private readonly string $code,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getItem(mixed $key): CacheItem
    {
        return $this->adapter->getItem($key);
    }

    public function getItems(array $keys = []): iterable
    {
        return $this->adapter->getItems($keys);
    }

    public function clear(string $prefix = ''): bool
    {
        return $this->adapter->clear($prefix);
    }

    public function hasItem(string $key): bool
    {
        return $this->adapter->hasItem($key);
    }

    public function deleteItem(string $key): bool
    {
        return $this->adapter->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->adapter->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->adapter->save($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->adapter->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->adapter->commit();
    }
}
