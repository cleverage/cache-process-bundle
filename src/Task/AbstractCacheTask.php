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

use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractCacheTask extends AbstractConfigurableTask
{
    public function __construct(protected AdapterRegistry $registry)
    {
    }

    /**
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['adapter', 'key']);

        $resolver->setAllowedTypes('adapter', ['string']);
        $resolver->setAllowedTypes('key', ['string']);
    }

    /**
     * Resolve the options merged with the input keys matching a defined option, the other input keys are ignored.
     *
     * @return array<mixed>
     *
     * @throws ExceptionInterface
     */
    protected function getMergedOptions(ProcessState $state): array
    {
        /** @var array<mixed> $options */
        $options = $this->getOptions($state);

        $input = $state->getInput() ?: [];
        if (!\is_array($input)) {
            throw new \UnexpectedValueException(\sprintf('%s expects an array or null input, %s given', (new \ReflectionClass($this))->getShortName(), get_debug_type($input)));
        }

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        return $resolver->resolve(array_merge($options, array_intersect_key($input, array_flip($resolver->getDefinedOptions()))));
    }
}
