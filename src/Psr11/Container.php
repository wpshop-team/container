<?php

namespace WPShop\Container\Psr11;

use Psr\Container\ContainerInterface;
use WPShop\Container\ServiceRegistry;

class Container implements ContainerInterface
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
