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
 *      key: string,
 *      value: mixed
 * }
 */
class SetTask extends AbstractCacheTask
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

        $item = $cache->getItem($mergedOptions['key'])->set($mergedOptions['value']);

        $cache->save($item);
    }

    /**
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['value']);
    }
}
