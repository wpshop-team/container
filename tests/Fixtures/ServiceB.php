<?php

namespace WPShopTest\Container\Fixtures;

class ServiceB
{
    public function __construct(ServiceC $dep)
    {

    }
}
