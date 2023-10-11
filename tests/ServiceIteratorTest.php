<?php

namespace WPShopTest\Container;

use PHPUnit\Framework\TestCase;
use WPShop\Container\ServiceIterator;
use WPShop\Container\ServiceRegistry;
use WPShopTest\Container\Fixtures\DummyService;

class ServiceIteratorTest extends TestCase
{
    public function testIteratesServices()
    {
        $registry = new ServiceRegistry([
            'foo'      => function () {
                return 'bar';
            },
            'service'  => function () {
                return new DummyService();
            },
            'service2' => function () {
                return new DummyService();
            },
        ]);

        $iterator = new ServiceIterator($registry, ['foo', 'service']);

        $this->assertSame(
            ['foo' => $registry['foo'], 'service' => $registry['service']],
            iterator_to_array($iterator)
        );
    }
}
