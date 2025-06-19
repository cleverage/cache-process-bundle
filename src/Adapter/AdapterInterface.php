<?php

declare(strict_types=1);

namespace CleverAge\CacheProcessBundle\Adapter;

interface AdapterInterface extends \Symfony\Component\Cache\Adapter\AdapterInterface
{
    /**
     * Return the code of the adapter used in adapter registry.
     */
    public function getCode(): string;
}
