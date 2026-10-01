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
 *      value: mixed,
 *      expires_after: int|null
 * }
 */
class SetTask extends AbstractCacheTask
{
    /**
     * @throws \Throwable
     */
    public function execute(ProcessState $state): void
    {
        /** @var Options $mergedOptions */
        $mergedOptions = $this->getMergedOptions($state);

        $cache = $this->registry->getAdapter($mergedOptions['adapter']);

        $item = $cache->getItem($mergedOptions['key'])->set($mergedOptions['value']);
        if (null !== $mergedOptions['expires_after']) {
            $item->expiresAfter($mergedOptions['expires_after']);
        }

        $cache->save($item);
    }

    /**
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    #[\Override]
    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined(['value']);

        $resolver->setDefault('expires_after', null);
        $resolver->setAllowedTypes('expires_after', ['null', 'int']);
        $resolver->setAllowedValues('expires_after', static fn (?int $value): bool => null === $value || $value > 0);
    }

    #[\Override]
    protected function getRequiredOptions(): array
    {
        return [...parent::getRequiredOptions(), 'value'];
    }
}
