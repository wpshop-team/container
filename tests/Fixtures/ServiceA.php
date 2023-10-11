<?php

namespace WPShopTest\Container\Fixtures;

class ServiceA
{
    public function __construct(ServiceB $dep)
    {

    }
}
