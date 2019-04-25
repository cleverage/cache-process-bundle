<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\CacheProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Assert the correct behavior of cache getter
 */
class GetterTransformerTest extends AbstractProcessTest
{
    /** @var CacheItemPoolInterface|null */
    protected $cache;

    /**
     * @throws InvalidArgumentException
     */
    public function testGetExistingCache()
    {
        if ($this->cache) {
            $input = [
                [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => ['something'],
                ],
                [
                    'key1' => 'value1b',
                    'key2' => 'value2b',
                    'key3' => ['something'],
                ],
                [
                    'key1' => 'value1c',
                    'key2' => 'value2c',
                    'key3' => [],
                ],
            ];

            $cacheItem = $this->cache->getItem('GetterTransformerTest_testGetExistingCache');
            $cacheItem->set($input);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_getter_transformer.get_existing_cache');
            self::assertEquals($input, $result);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetMissingCache()
    {
        if ($this->cache) {
            $result = $this->processManager->execute('test.cache_getter_transformer.get_missing_cache');
            self::assertEquals('missing cache', $result);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testIgnoreMissingCache()
    {
        if ($this->cache) {
            $result = $this->processManager->execute('test.cache_getter_transformer.ignore_missing_cache');
            self::assertNull($result);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testTransformCacheKey()
    {
        if ($this->cache) {
            $input = ['GetterTransformerTest', 'testTransformCacheKey'];

            $cacheItem = $this->cache->getItem('GetterTransformerTest_testTransformCacheKey');
            $cacheItem->set($input);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_getter_transformer.transform_cache_key', $input);
            self::assertEquals($input, $result);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBadCacheKey()
    {
        if ($this->cache) {
            $input = ['GetterTransformerTest', 'testBadCacheKey'];

            $cacheItem = $this->cache->getItem('GetterTransformerTest_testBadCacheKey');
            $cacheItem->set($input);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_getter_transformer.bad_cache_key', $input);
            self::assertEquals('missing cache', $result);
        }
    }

    protected function setUp()
    {
        parent::setUp();

        if (static::$kernel->getContainer()->has('cache.app')) {
            $this->cache = static::$kernel->getContainer()->get('cache.app');
        }
    }
}
