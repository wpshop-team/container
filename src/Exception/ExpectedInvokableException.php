<?php

namespace WPShop\Container\Exception;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

class ExpectedInvokableException extends InvalidArgumentException implements ContainerExceptionInterface
{

}
