<?php

namespace WPShop\Container;

class ServiceIterator implements \Iterator
{
    /**
     * @var ServiceRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $ids;

    /**
     * @param ServiceRegistry $registry
     * @param array $ids
     */
    public function __construct(ServiceRegistry $registry, array $ids)
    {
        $this->registry = $registry;
        $this->ids = $ids;
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->registry[\current($this->ids)];
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        \next($this->ids);
    }

    /**
     * @return false|mixed|null
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return \current($this->ids);
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return null !== \key($this->ids);
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        \reset($this->ids);
    }
}
