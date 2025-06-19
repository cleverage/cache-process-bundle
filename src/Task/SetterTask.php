<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\CacheProcessBundle\src\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SetterTask
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class SetterTask extends AbstractCacheTask
{
    /**
     * @param ProcessState $state
     *
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $keyValue = $this->getKeyCache($state);
        $input = $state->getInput();

        $cacheItem = $this->getCache()->getItem($keyValue);
        $cachedValue = $this->transformValue($input, $this->getOption($state, 'value'));
        $cacheItem->set($cachedValue);
        $this->getCache()->save($cacheItem);

        $state->setOutput($input);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(
            [
                'value',
            ]
        );
        $resolver->setAllowedTypes('value', ['array', 'null']);

        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'value',
            function (Options $options, $value) {
                $mappingResolver = new OptionsResolver();
                $this->configureMappingOptions($mappingResolver);

                return $mappingResolver->resolve(
                    $value ?? []
                );
            }
        );

        return $resolver;
    }
}
