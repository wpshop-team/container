<?php

namespace WPShopTest\Container;

use PHPUnit\Framework\TestCase;
use WPShop\Container\Exception\CyclicDependenciesException;
use WPShop\Container\Exception\ExpectedInvokableException;
use WPShop\Container\Exception\FrozenServiceException;
use WPShop\Container\Exception\InvalidServiceIdentifierException;
use WPShop\Container\Exception\UnknownIdentifierException;
use WPShop\Container\ServiceProviderInterface;
use WPShop\Container\ServiceRegistry;
use WPShopTest\Container\Fixtures\DummyService;
use WPShopTest\Container\Fixtures\Invokable;
use WPShopTest\Container\Fixtures\NonInvokable;
use WPShopTest\Container\Fixtures\ServiceA;
use WPShopTest\Container\Fixtures\ServiceB;
use WPShopTest\Container\Fixtures\ServiceC;

class ServiceRegistryTest extends TestCase
{
    public function testInjectsWithConstructor()
    {
        $params = ['param' => 'value'];
        $registry = new ServiceRegistry($params);

        $this->assertSame($params['param'], $registry['param']);
    }

    public function testReturnsWithString()
    {
        $registry = new ServiceRegistry(['param' => 'value']);

        $this->assertEquals('value', $registry['param']);
    }

    public function testReturnsWithClosure()
    {
        $registry = new ServiceRegistry(['param' => function () {
            return new DummyService();
        }]);

        $this->assertInstanceOf(DummyService::class, $registry['param']);
    }

    /**
     * @param callable $service
     * @return void
     * @dataProvider serviceDefinitionProvider
     */
    public function testSharesService($service)
    {
        $registry = new ServiceRegistry();
        $registry['shared_service'] = $service;

        $serviceOne = $registry['shared_service'];
        $serviceTwo = $registry['shared_service'];

        $this->assertInstanceOf(DummyService::class, $serviceOne);
        $this->assertInstanceOf(DummyService::class, $serviceTwo);
        $this->assertSame($serviceOne, $serviceTwo);
    }

    public function testFactoryReturnsUniqueService()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = $registry->factory(function () {
            return new DummyService();
        });

        $serviceOne = $registry['service'];
        $serviceTwo = $registry['service'];

        $this->assertInstanceOf(DummyService::class, $serviceOne);
        $this->assertInstanceOf(DummyService::class, $serviceTwo);

        $this->assertNotSame($serviceOne, $serviceTwo);
    }

    public function testPassesSelfAsParameter()
    {
        $registry = new ServiceRegistry();
        $registry['container'] = function ($c) {
            return $c;
        };

        $this->assertSame($registry, $registry['container']);
    }

    public function testProperWorksWithIsset()
    {
        $registry = new ServiceRegistry();
        $registry['param'] = 'value';
        $registry['service'] = function () {
            return new DummyService();
        };
        $registry['null'] = null;

        $this->assertTrue(isset($registry['param']));
        $this->assertTrue(isset($registry['service']));
        $this->assertTrue(isset($registry['null']));
        $this->assertFalse(isset($registry['not_existing']));
    }

    public function testProperWorksWithUnset()
    {
        $registry = new ServiceRegistry();
        $registry['param'] = 'value';
        $registry['service'] = function () {
            return new DummyService();
        };

        unset($registry['param'], $registry['service']);

        $this->assertFalse(isset($registry['param']));
        $this->assertFalse(isset($registry['service']));
    }

    public function testThrowsUnknownIdentifierException()
    {
        $registry = new ServiceRegistry();

        $this->expectException(UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "foo" is not defined.');

        $registry['foo'];
    }

    /**
     * @param callable $service
     * @return void
     * @dataProvider serviceDefinitionProvider
     */
    public function testProtectsAService($service)
    {
        $registry = new ServiceRegistry();
        $registry['protected_service'] = $registry->protect($service);

        $this->assertSame($service, $registry['protected_service']);
    }

    public function testStoresRawDefinition()
    {
        $registry = new ServiceRegistry();
        $registry['service'] = $definition = $registry->factory(function () {
            return 'foo';
        });

        $this->assertSame($definition, $registry->raw('service'));
    }

    public function testReturnsStoredRawNull()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = null;

        $this->assertNull($registry['foo']);
    }

    public function testRegistersInFluentWay()
    {
        $registry = new ServiceRegistry();

        $this->assertSame(
            $registry,
            $registry->register($this->getMockBuilder(ServiceProviderInterface::class)->getMock())
        );
    }

    public function testGetRawThrowsUnknownIdentifierException()
    {
        $registry = new ServiceRegistry();

        $this->expectException(UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "foo" is not defined.');

        $registry->raw('foo');
    }

    public function testProperExtendsService()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
            return 'foo';
        };
        $registry['foo'] = $registry->extend('foo', function ($foo, $c) {
            return "$foo.bar";
        });
        $registry['foo'] = $registry->extend('foo', function ($foo, $c) {
            return "$foo.baz";
        });

        $this->assertSame('foo.bar.baz', $registry['foo']);
    }

    public function testProperExtendsServiceAfterOtherServiceFreeze()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
            return 'foo';
        };
        $registry['bar'] = function () {
            return 'bar';
        };

        $foo = $registry['foo'];

        $registry['bar'] = $registry->extend('bar', function ($bar, $c) {
            return "$bar.baz";
        });

        $this->assertSame('bar.baz', $registry['bar']);
    }

    /**
     * @param callable $service
     * @return void
     * @dataProvider serviceDefinitionProvider
     */
    public function testProperExtendsSharedService($service)
    {
        $registry = new ServiceRegistry();
        $registry['shared_service'] = function () {
            return new DummyService();
        };

        $registry->extend('shared_service', $service);

        $serviceOne = $registry['shared_service'];
        $serviceTwo = $registry['shared_service'];

        $this->assertInstanceOf(DummyService::class, $serviceOne);
        $this->assertInstanceOf(DummyService::class, $serviceTwo);
        $this->assertSame($serviceOne, $serviceTwo);
        $this->assertSame($serviceOne->value, $serviceTwo->value);
    }

    /**
     * @param callable $service
     * @return void
     * @dataProvider serviceDefinitionProvider
     */
    public function testProperExtendsFactoryService($service)
    {
        $registry = new ServiceRegistry();
        $registry['factory_service'] = $registry->factory(function () {
            return new DummyService();
        });

        $registry->extend('factory_service', $service);

        $serviceOne = $registry['factory_service'];
        $serviceTwo = $registry['factory_service'];

        $this->assertInstanceOf(DummyService::class, $serviceOne);
        $this->assertInstanceOf(DummyService::class, $serviceTwo);
        $this->assertNotSame($serviceOne, $serviceTwo);
        $this->assertNotSame($serviceOne->value, $serviceTwo->value);
    }

    public function testExtendThrowsUnknownIdentifierException()
    {
        $registry = new ServiceRegistry();

        $this->expectException(UnknownIdentifierException::class);
        $this->expectExceptionMessage('Identifier "foo" is not defined.');

        $registry->extend('foo', function () {
        });
    }

    public function testReturnsKeys()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = 123;
        $registry['bar'] = 456;

        $this->assertEquals(['foo', 'bar'], $registry->keys());
    }

    public function testTreatsInvokableAsService()
    {
        $registry = new ServiceRegistry();
        $registry['invokable'] = new Invokable();

        $this->assertInstanceOf(DummyService::class, $registry['invokable']);
    }

    public function testTreatsNonInvokableAsParameter()
    {
        $registry = new ServiceRegistry();
        $registry['non_invokable'] = new NonInvokable();

        $this->assertInstanceOf(NonInvokable::class, $registry['non_invokable']);
    }

    /**
     * @param $badService
     * @return void
     * @dataProvider badServiceDefinitionProvider
     */
    public function testFactoryFailsForInvalidServiceDefinitions($badService)
    {
        $registry = new ServiceRegistry();

        $this->expectException(ExpectedInvokableException::class);
        $this->expectExceptionMessage('Service definition is not a Closure or invokable object.');

        $registry->factory($badService);
    }

    /**
     * @param $badService
     * @return void
     * @dataProvider badServiceDefinitionProvider
     */
    public function testProtectFailsForInvalidServiceDefinitions($badService)
    {
        $registry = new ServiceRegistry();

        $this->expectException(ExpectedInvokableException::class);
        $this->expectExceptionMessage('Callable is not a Closure or invokable object.');

        $registry->protect($badService);
    }

    /**
     * @param $badService
     * @return void
     * @dataProvider badServiceDefinitionProvider
     */
    public function testExtendFailsForKeysNotContainingServiceDefinitions($badService)
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = $badService;

        $this->expectException(InvalidServiceIdentifierException::class);
        $this->expectExceptionMessage('Identifier "foo" does not contain an object definition.');

        $registry->extend('foo', function () {

        });
    }

    public function testTriggersWarningOnExtendProtectedService()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = $registry->protect(function () {
            return 'bar';
        });

        $this->expectWarning();
        $this->expectWarningMessage('Are you sure "foo" should be protected if you are trying to extend it?');

        $registry->extend('foo', function ($val) {
            return $val . '-baz';
        });

        $this->assertSame('bar-baz', $registry['foo']);
    }

    /**
     * @param $badService
     * @return void
     * @dataProvider badServiceDefinitionProvider
     */
    public function testExtendFailsForInvalidServiceDefinitions($badService)
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
        };

        $this->expectException(ExpectedInvokableException::class);
        $this->expectExceptionMessage('Extension service definition is not a Closure or invokable object.');

        $registry->extend('foo', $badService);
    }

    public function testExtendFailsIfFrozenServiceIsNonInvokable()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
            return new NonInvokable();
        };
        $foo = $registry['foo'];

        $this->expectException(FrozenServiceException::class);
        $this->expectExceptionMessage('Cannot override frozen service "foo".');

        $registry->extend('foo', function () {
        });
    }

    public function testExtendFailsIfFrozenServiceIsInvokable()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
            return new Invokable();
        };
        $foo = $registry['foo'];

        $this->expectException(FrozenServiceException::class);
        $this->expectExceptionMessage('Cannot override frozen service "foo".');

        $registry->extend('foo', function () {
        });
    }

    public function testCanDefineServiceAfterFreeze()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
            return 'foo';
        };

        $foo = $registry['foo'];

        $registry['bar'] = function () {
            return 'bar';
        };

        $this->assertSame('bar', $registry['bar']);
    }

    public function testFailsToOverrideServiceAfterFreeze()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
            return 'foo';
        };
        $foo = $registry['foo'];

        $this->expectException(FrozenServiceException::class);
        $this->expectExceptionMessage('Cannot override frozen service "foo".');

        $registry['foo'] = function () {
            return 'bar';
        };
    }

    public function testRemovesServiceAfterFreeze()
    {
        $registry = new ServiceRegistry();
        $registry['foo'] = function () {
            return 'foo';
        };
        $foo = $registry['foo'];

        unset($registry['foo']);

        $registry['foo'] = function () {
            return 'bar';
        };

        $this->assertSame('bar', $registry['foo']);
    }

    public function testCatchesCyclicDependencies()
    {
        $registry = new ServiceRegistry([
            ServiceA::class => function ($c) {
                return new ServiceA($c[ServiceB::class]);
            },
            ServiceB::class => function ($c) {
                return new ServiceB($c[ServiceC::class]);
            },
            ServiceC::class => function ($c) {
                return new ServiceC($c[ServiceA::class]);
            },
        ]);

        $this->expectException(CyclicDependenciesException::class);

        $registry->offsetGet(ServiceA::class);
    }

    /**
     * Provider for service definitions.
     * @return array
     */
    public function serviceDefinitionProvider()
    {
        return [
            [function ($value) {
                $service = new DummyService();
                $service->value = $value;

                return $service;
            }],
            [new Invokable()],
        ];
    }

    /**
     * Provider for invalid service definitions.
     * @return array
     */
    public function badServiceDefinitionProvider()
    {
        return [
            [123],
            [new Fixtures\NonInvokable()],
        ];
    }
}
