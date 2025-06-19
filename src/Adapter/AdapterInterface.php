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

interface AdapterInterface extends \Symfony\Component\Cache\Adapter\AdapterInterface
{
    /**
     * Return the code of the adapter used in adapter registry.
     */
    public function getCode(): string;
}
