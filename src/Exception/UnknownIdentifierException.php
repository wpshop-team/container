<?php

namespace WPShop\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

class UnknownIdentifierException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    /**
     * @param string $id The unknown identifier
     */
    public function __construct($id)
    {
        parent::__construct(\sprintf('Identifier "%s" is not defined.', $id));
    }
}
