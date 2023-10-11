<?php

namespace WPShopTest\Container\Psr11;

use PHPUnit\Framework\TestCase;
use WPShop\Container\Psr11\Container;
use WPShop\Container\ServiceRegistry;
use WPShopTest\Container\Fixtures\DummyService;

class ContainerTest extends TestCase
{

    public function testGetReturnsExistingService()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = function () {
            return new DummyService();
        };

        $psrContainer = new Container($registry);

        $this->assertSame($registry['service'], $psrContainer->get('service'));
    }

    public function testGetThrowsExceptionIfServiceIsNotFound()
    {
        $registry = new ServiceRegistry();
        $psrContainer = new Container($registry);

        $this->expectException(\Psr\Container\NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Identifier "service" is not defined.');

        $psrContainer->get('service');
    }

    public function testHasReturnsTrueIfServiceExists()
    {
        $registry = new ServiceRegistry();

        $registry['service'] = function () {
            return new DummyService();
        };
        $psrContainer = new Container($registry);

        $this->assertTrue($psrContainer->has('service'));
    }

    public function testHasReturnsFalseIfServiceDoesNotExist()
    {
        $registry = new ServiceRegistry();
        $psrContainer = new Container($registry);

        $this->assertFalse($psrContainer->has('service'));
    }
}
