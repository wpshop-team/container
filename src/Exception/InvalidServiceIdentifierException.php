<?php

namespace WPShop\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

class InvalidServiceIdentifierException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    /**
     * @param string $id The invalid identifier
     */
    public function __construct($id)
    {
        parent::__construct(\sprintf('Identifier "%s" does not contain an object definition.', $id));
    }
}
