<?php

namespace WPShop\Container;

/**
 * Just container without implementation of the Psr\Container\ContainerInterface
 */
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

    public function get($id)
    {
        return $this->registry[$id];
    }

    public function has($id)
    {
        return isset($this->registry[$id]);
    }
}
