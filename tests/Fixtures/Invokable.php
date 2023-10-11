<?php

namespace WPShopTest\Container\Fixtures;

class Invokable
{
    public function __invoke($value = null)
    {
        $service = new DummyService();
        $service->value = $value;

        return $service;
    }
}
