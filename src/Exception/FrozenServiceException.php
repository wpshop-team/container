<?php

namespace WPShop\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class FrozenServiceException extends RuntimeException implements ContainerExceptionInterface
{
    /**
     * @param string $id Identifier of the frozen service
     */
    public function __construct($id)
    {
        parent::__construct(\sprintf('Cannot override frozen service "%s".', $id));
    }
}
