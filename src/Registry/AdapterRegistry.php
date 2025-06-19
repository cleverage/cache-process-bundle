<?php

declare(strict_types=1);

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

    public function addAdapter(AdapterInterface $adapter): void
    {
        if (\array_key_exists($adapter->getCode(), $this->adapters)) {
            throw new \UnexpectedValueException("Adapter {$adapter->getCode()} is already defined");
        }
        $this->adapters[$adapter->getCode()] = $adapter;
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
