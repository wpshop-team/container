<?php

namespace WPShop\Container\Psr11;

use WPShop\Container\ServiceRegistry;

class Container
{
    /**
     * @var ServiceRegistry
     */
    protected $registry;

    /**
     * @param ServiceRegistry $registry
     */
    public function __construct(ServiceRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function get(string $id)
    {
        return $this->registry[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->registry[$id]);
    }
}
