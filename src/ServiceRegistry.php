<?php

namespace WPShop\Container;

use ArrayAccess;
use SplObjectStorage;
use WPShop\Container\Exception\CyclicDependenciesException;
use WPShop\Container\Exception\ExpectedInvokableException;
use WPShop\Container\Exception\FrozenServiceException;
use WPShop\Container\Exception\InvalidServiceIdentifierException;
use WPShop\Container\Exception\UnknownIdentifierException;

class ServiceRegistry implements ArrayAccess
{
    /**
     * @var SplObjectStorage
     */
    protected $factories;

    /**
     * @var SplObjectStorage
     */
    protected $protected;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $frozen = [];

    /**
     * @var array
     */
    protected $raw = [];

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @var array
     */
    protected $creating = [];

    /**
     * @param array $values The parameters or objects
     */
    public function __construct(array $values = [])
    {
        $this->factories = new SplObjectStorage();
        $this->protected = new SplObjectStorage();

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $id The unique identifier for the parameter or object
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($id)
    {
        return isset($this->keys[$id]);
    }

    /**
     * Gets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     * @return mixed The value of the parameter or an object
     *
     * @throws UnknownIdentifierException If the identifier is not defined
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($id)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (
            isset($this->raw[$id])
            || !\is_object($this->values[$id])
            || isset($this->protected[$this->values[$id]])
            || !\method_exists($this->values[$id], '__invoke')
        ) {
            return $this->values[$id];
        }

        if (isset($this->creating[$id])) {
            throw new CyclicDependenciesException('Looks like there are cyclic dependencies');
        }

        if (isset($this->factories[$this->values[$id]])) {
            $this->creating[$id] = $id;

            $val = $this->values[$id]($this);

            unset($this->creating[$id]);
            return $val;
        }

        $this->creating[$id] = $id;

        $raw = $this->values[$id];
        $val = $this->values[$id] = $raw($this);
        $this->raw[$id] = $raw;

        $this->frozen[$id] = true;

        unset($this->creating[$id]);
        return $val;
    }

    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads too difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same name as an existing parameter would break your container).
     *
     * @param string $id The unique identifier for the parameter or object
     * @param mixed $value the value of the parameter or a closure to define an object
     * @return void
     *
     * @throws FrozenServiceException Prevent override of a frozen service
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($id, $value)
    {
        if (isset($this->frozen[$id])) {
            throw new FrozenServiceException($id);
        }

        $this->values[$id] = $value;
        $this->keys[$id] = true;
    }

    /**
     * Unsets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($id)
    {
        if (isset($this->keys[$id])) {
            if (\is_object($this->values[$id])) {
                unset($this->factories[$this->values[$id]], $this->protected[$this->values[$id]]);
            }

            unset($this->values[$id], $this->frozen[$id], $this->raw[$id], $this->keys[$id]);
        }
    }

    /**
     * @param callable $callable A service definition to be used as a factory
     * @return callable The passed callable
     */
    public function factory($callable)
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Service definition is not a Closure or invokable object.');
        }

        $this->factories->attach($callable);

        return $callable;
    }

    /**
     * Protects a callable from being interpreted as a service.
     *
     * This is useful when you want to store a callable as a parameter.
     *
     * @param callable $callable A callable to protect from being evaluated
     * @return callable The passed callable
     *
     * @throws ExpectedInvokableException Service definition has to be a closure or an invokable object
     */
    public function protect($callable)
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Callable is not a Closure or invokable object.');
        }

        $this->protected->attach($callable);

        return $callable;
    }

    /**
     * Gets a parameter or the closure defining an object.
     *
     * @param string $id The unique identifier for the parameter or object
     * @return mixed The value of the parameter or the closure defining an object
     *
     * @throws UnknownIdentifierException If the identifier is not defined
     */
    public function raw($id)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (isset($this->raw[$id])) {
            return $this->raw[$id];
        }

        return $this->values[$id];
    }

    /**
     * Extends an object definition.
     *
     * Useful when you want to extend an existing object definition,
     * without necessarily loading that object.
     *
     * @param string $id The unique identifier for the object
     * @param callable $callable A service definition to extend the original
     * @return \Closure|callable The wrapped callable
     *
     * @throws UnknownIdentifierException        If the identifier is not defined
     * @throws FrozenServiceException            If the service is frozen
     * @throws InvalidServiceIdentifierException If the identifier belongs to a parameter
     * @throws ExpectedInvokableException        If the extension callable is not a closure or an invokable object
     */
    public function extend($id, $callable)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (isset($this->frozen[$id])) {
            throw new FrozenServiceException($id);
        }

        if (!\is_object($this->values[$id]) || !\method_exists($this->values[$id], '__invoke')) {
            throw new InvalidServiceIdentifierException($id);
        }

        if (isset($this->protected[$this->values[$id]])) {
            \trigger_error(\sprintf('Are you sure "%s" should be protected if you are trying to extend it?', $id), E_USER_WARNING);
        }

        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Extension service definition is not a Closure or invokable object.');
        }

        $factory = $this->values[$id];

        $extended = function ($c) use ($callable, $factory) {
            return $callable($factory($c), $c);
        };

        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        return $this[$id] = $extended;
    }

    /**
     * Returns all defined value names.
     *
     * @return string[] An array of value names
     */
    public function keys()
    {
        return \array_keys($this->values);
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider
     * @param array $values
     * @return $this
     */
    public function register(ServiceProviderInterface $provider, array $values = [])
    {
        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }
}
