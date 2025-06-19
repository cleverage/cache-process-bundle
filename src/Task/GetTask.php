<?php

declare(strict_types=1);

namespace CleverAge\CacheProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
//        /** @var Options $options */
//        $options = $this->getOptions($state);
//
//        /** @var array<mixed> $input */
//        $input = $state->getInput() ?: [];
//
//        /** @var Options $mergedOptions */
//        $mergedOptions = array_merge($options, $input);
        /** @var Options $mergedOptions */
        $mergedOptions = $this->getMergedOptions($state);

        $cache = $this->registry->getAdapter($mergedOptions['adapter']);

        $state->setOutput($cache->getItem($mergedOptions['key'])->get());
    }
}
