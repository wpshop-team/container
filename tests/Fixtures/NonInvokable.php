<?php

namespace WPShopTest\Container\Fixtures;

class NonInvokable
{
    public function __call($a, $b) {

    }
}
