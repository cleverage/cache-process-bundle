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

/**
 * Holds all tagged cache adapters services.
 */
class AdapterRegistry
{
    /** @var AdapterInterface[] */
    private array $adapters = [];

    /** @var array<string, string|null> Service ids of the adapters, indexed by code */
    private array $serviceIds = [];

    /**
     * @param string|null $serviceId Id of the adapter service, used to identify the adapters with the same code
     */
    public function addAdapter(AdapterInterface $adapter, ?string $serviceId = null): void
    {
        $code = $adapter->getCode();
        if (\array_key_exists($code, $this->adapters)) {
            $message = "Adapter {$code} is already defined";
            if (null !== $this->serviceIds[$code] && null !== $serviceId) {
                $message .= " by service \"{$this->serviceIds[$code]}\", cannot register service \"{$serviceId}\"";
            }

            throw new \UnexpectedValueException($message);
        }
        $this->adapters[$code] = $adapter;
        $this->serviceIds[$code] = $serviceId;
    }

    /**
     * @throws MissingAdapterException
     */
    public function getAdapter(string $code): AdapterInterface
    {
        if (!\array_key_exists($code, $this->adapters)) {
            throw new MissingAdapterException("Adapter {$code} is missing");
        }

        return $this->adapters[$code];
    }
}
