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

namespace CleverAge\CacheProcessBundle\Attribute;

/**
 * Registers a cache adapter service under its code, so that it is only instantiated when a process uses it.
 *
 * Requires autoconfiguration; equivalent to a `cleverage.cache.adapter` tag with a `code` attribute. The code must be
 * the one returned by `AdapterInterface::getCode()`.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsCacheAdapter
{
    public function __construct(
        public string $code,
    ) {
    }
}
