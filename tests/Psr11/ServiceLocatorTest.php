<?php

namespace WPShopTest\Container\Psr11;

use PHPUnit\Framework\TestCase;
use WPShop\Container\Exception\UnknownIdentifierException;
use WPShop\Container\Psr11\ServiceLocator;
use WPShop\Container\ServiceRegistry;
use WPShopTest\Container\Fixtures\DummyService;

class ServiceLocatorTest extends TestCase
{
    public function testCanAccessServices()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = function () {
            return new DummyService();
        };

        $locator = new ServiceLocator($registry, ['service']);

        $this->assertSame($registry['service'], $locator->get('service'));
    }

    public function testCanAccessAliasedServices()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = function () {
            return new DummyService();
        };

        $locator = new ServiceLocator($registry, ['alias' => 'service']);

        $this->assertSame($registry['service'], $locator->get('alias'));
    }

    public function testCannotAccessAliasedServiceUsingRealIdentifier()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = function () {
            return new DummyService();
        };
        $locator = new ServiceLocator($registry, ['alias' => 'service']);

        $this->expectException(UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "service" is not defined.');

        $locator->get('service');
    }

    public function testGetValidatesServiceCanBeLocated()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = function () {
            return new DummyService();
        };
        $locator = new ServiceLocator($registry, ['alias' => 'service']);

        $this->expectException(UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "foo" is not defined.');

        $locator->get('foo');
    }

    public function testGetValidatesTargetServiceExists()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = function () {
            return new DummyService();
        };
        $locator = new ServiceLocator($registry, ['alias' => 'invalid']);

        $this->expectException(UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "invalid" is not defined.');

        $locator->get('alias');
    }

    public function testHasValidatesServiceCanBeLocated()
    {
        $registry = new ServiceRegistry();
        $registry['service1'] = function () {
            return new DummyService();
        };
        $registry['service2'] = function () {
            return new DummyService();
        };
        $locator = new ServiceLocator($registry, ['service1']);

        $this->assertTrue($locator->has('service1'));
        $this->assertFalse($locator->has('service2'));
    }

    public function testHasChecksIfTargetServiceExists()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = function () {
            return new DummyService();
        };
        $locator = new ServiceLocator($registry, ['foo' => 'service', 'bar' => 'invalid']);

        $this->assertTrue($locator->has('foo'));
        $this->assertFalse($locator->has('bar'));
    }
}
