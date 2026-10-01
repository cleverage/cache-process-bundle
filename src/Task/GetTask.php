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
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type Options array{
 *      adapter: string,
 *      key: string,
 *      on_miss: string
 * }
 */
class GetTask extends AbstractCacheTask
{
    /** Output null on a cache miss, as for an item stored with a null value */
    public const ON_MISS_OUTPUT_NULL = 'output_null';

    /** Skip the item and send the input to the error outputs on a cache miss */
    public const ON_MISS_SKIP = 'skip';

    /** Throw an exception on a cache miss */
    public const ON_MISS_FAIL = 'fail';

    /**
     * @throws \Throwable
     */
    public function execute(ProcessState $state): void
    {
        /** @var Options $mergedOptions */
        $mergedOptions = $this->getMergedOptions($state);

        $cache = $this->registry->getAdapter($mergedOptions['adapter']);
        $item = $cache->getItem($mergedOptions['key']);

        if (!$item->isHit()) {
            if (self::ON_MISS_SKIP === $mergedOptions['on_miss']) {
                $state->setErrorOutput($state->getInput());
                $state->setSkipped(true);

                return;
            }
            if (self::ON_MISS_FAIL === $mergedOptions['on_miss']) {
                throw new \UnexpectedValueException("Cache item {$mergedOptions['key']} is missing from adapter {$mergedOptions['adapter']}");
            }
        }

        $state->setOutput($item->get());
    }

    /**
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    #[\Override]
    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('on_miss', self::ON_MISS_OUTPUT_NULL);
        $resolver->setAllowedValues('on_miss', [self::ON_MISS_OUTPUT_NULL, self::ON_MISS_SKIP, self::ON_MISS_FAIL]);
    }
}
