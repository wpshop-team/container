<?php

namespace WPShop\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class CyclicDependenciesException extends RuntimeException implements ContainerExceptionInterface
{
}
