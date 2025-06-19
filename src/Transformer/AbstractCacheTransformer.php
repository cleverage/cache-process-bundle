<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\CacheProcessBundle\src\Transformer;

use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class AbstractCacheTransformer
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
abstract class AbstractCacheTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * SetterTask constructor.
     *
     * @param LoggerInterface           $logger
     * @param PropertyAccessorInterface $accessor
     * @param CacheItemPoolInterface    $cache
     * @param TransformerRegistry       $transformerRegistry
     */
    public function __construct(
        LoggerInterface $logger,
        PropertyAccessorInterface $accessor,
        CacheItemPoolInterface $cache,
        TransformerRegistry $transformerRegistry
    ) {
        $this->logger = $logger;
        $this->accessor = $accessor;
        $this->cache = $cache;
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'key',
            ]
        );
        $resolver->setAllowedTypes('key', ['array', 'null']);

        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'key',
            function (Options $options, $value) {
                $mappingResolver = new OptionsResolver();
                $this->configureMappingOptions($mappingResolver);

                return $mappingResolver->resolve(
                    $value ?? []
                );
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws ExceptionInterface
     */
    protected function configureMappingOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'code' => null, // Source property
                'constant' => null,
            ]
        );
        $resolver->setAllowedTypes('code', ['NULL', 'string', 'array']);

        $this->configureTransformersOptions($resolver);
    }

    /**
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    protected function getKeyCache($value, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        return $this->transformValue($value, $options['key']);
    }
}
