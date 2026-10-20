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

namespace CleverAge\CacheProcessBundle\Exception;

use CleverAge\ProcessBundle\Exception\ProcessExceptionInterface;

/**
 * Exception thrown when trying to fetch a missing Cache Adapter.
 */
class MissingAdapterException extends \Exception implements ProcessExceptionInterface
{
}
