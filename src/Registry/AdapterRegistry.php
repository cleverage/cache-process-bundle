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

namespace CleverAge\CacheProcessBundle\Registry;

use CleverAge\CacheProcessBundle\Adapter\AdapterInterface;
use CleverAge\CacheProcessBundle\Exception\MissingAdapterException;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Holds the cache adapters, indexed by code.
 *
 * The adapter services are fetched lazily from a service locator indexed by code (`code` attribute of the
 * `cleverage.cache.adapter` tag, or `#[AsCacheAdapter]`, see RegisterAdaptersPass): an adapter is only instantiated
 * when it is used.
 *
 * @final since 2.3
 */
class AdapterRegistry
{
    /**
     * Adapters already fetched from the locator, and the ones registered with addAdapter() (deprecated).
     *
     * @var array<string, AdapterInterface>
     */
    private array $adapters = [];

    /** @var array<string, string|null> Service ids of the adapters registered with addAdapter(), indexed by code */
    private array $serviceIds = [];

    /**
     * @param ServiceProviderInterface<mixed>|null $locator Adapter services, checked when fetched
     */
    public function __construct(
        private readonly ?ServiceProviderInterface $locator = null,
    ) {
    }

    /**
     * @param string|null $serviceId Id of the adapter service, used to identify the adapters with the same code
     *
     * @deprecated since 2.3, tag the adapter service with a "code" attribute or use #[AsCacheAdapter] instead
     */
    public function addAdapter(AdapterInterface $adapter, ?string $serviceId = null): void
    {
        $code = $adapter->getCode();
        trigger_deprecation(
            'cleverage/cache-process-bundle',
            '2.3',
            'Registering the cache adapter "%s" (%s) with "%s()" is deprecated, tag its service with a "code" attribute ({ name: cleverage.cache.adapter, code: %s }) or use the #[AsCacheAdapter] attribute instead, so that it is only instantiated when used. It will be required in 3.0.',
            $code,
            $adapter::class,
            __METHOD__,
            $code,
        );
        if ($this->hasAdapter($code)) {
            $message = "Adapter {$code} is already defined";
            $definedBy = $this->serviceIds[$code] ?? null;
            if (null !== $definedBy && null !== $serviceId) {
                $message .= " by service \"{$definedBy}\", cannot register service \"{$serviceId}\"";
            }

            throw new \UnexpectedValueException($message);
        }
        $this->adapters[$code] = $adapter;
        $this->serviceIds[$code] = $serviceId;
    }

    /**
     * Instantiates every adapter of the locator.
     *
     * @return array<string, AdapterInterface>
     */
    public function getAdapters(): array
    {
        foreach (array_keys($this->locator?->getProvidedServices() ?? []) as $code) {
            $this->getAdapter((string) $code);
        }

        return $this->adapters;
    }

    /**
     * @throws MissingAdapterException
     */
    public function getAdapter(string $code): AdapterInterface
    {
        if (isset($this->adapters[$code])) {
            return $this->adapters[$code];
        }
        if (!$this->locator?->has($code)) {
            throw new MissingAdapterException("Adapter {$code} is missing");
        }

        $adapter = $this->locator->get($code);
        if (!$adapter instanceof AdapterInterface) {
            throw new \UnexpectedValueException(\sprintf('The service of the cache adapter "%s" must implement "%s".', $code, AdapterInterface::class));
        }
        if ($code !== $adapter->getCode()) {
            throw new \UnexpectedValueException(\sprintf('The cache adapter "%s" is registered with the code "%s" but its getCode() method returns "%s": both must be the same.', $adapter::class, $code, $adapter->getCode()));
        }

        return $this->adapters[$code] = $adapter;
    }

    public function hasAdapter(string $code): bool
    {
        return isset($this->adapters[$code]) || ($this->locator?->has($code) ?? false);
    }
}
