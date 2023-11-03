<?php

namespace WPShop\Container\Psr11;

use Psr\Container\ContainerInterface;
use WPShop\Container\Exception\UnknownIdentifierException;
use WPShop\Container\ServiceRegistry;

class ServiceLocator implements ContainerInterface
{
    protected $registry;
    protected $aliases = [];

    public function __construct(ServiceRegistry $registry, array $ids)
    {
        $this->registry = $registry;

        foreach ($ids as $key => $id) {
            $this->aliases[\is_int($key) ? $id : $key] = $id;
        }
    }

    public function get($id)
    {
        if (!isset($this->aliases[$id])) {
            throw new UnknownIdentifierException($id);
        }

        return $this->registry[$this->aliases[$id]];
    }

    public function has($id)
    {
        return isset($this->aliases[$id]) && isset($this->registry[$this->aliases[$id]]);
    }
}
