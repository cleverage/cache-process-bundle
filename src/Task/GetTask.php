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

namespace CleverAge\CacheProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * @phpstan-type Options array{
 *      adapter: string,
 *      key: string
 * }
 */
class GetTask extends AbstractCacheTask
{
    /**
     * @throws \Throwable
     */
    public function execute(ProcessState $state): void
    {
        /** @var Options $mergedOptions */
        $mergedOptions = $this->getMergedOptions($state);

        $cache = $this->registry->getAdapter($mergedOptions['adapter']);

        $state->setOutput($cache->getItem($mergedOptions['key'])->get());
    }
}
