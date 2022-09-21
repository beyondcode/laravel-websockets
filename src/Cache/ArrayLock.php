<?php

namespace BeyondCode\LaravelWebSockets\Cache;

use BeyondCode\LaravelWebSockets\Helpers;
use Illuminate\Cache\ArrayLock as LaravelLock;
use React\Promise\PromiseInterface;

class ArrayLock extends Lock
{
    /**
     * The parent array cache store.
     *
     * @var \Illuminate\Cache\ArrayStore
     */
    protected $store;

    /**
     * Internal Laravel Array Lock.
     *
     * @var \Illuminate\Cache\ArrayLock
     */
    protected $lock;

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Cache\ArrayStore  $store
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct($store, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->lock = new LaravelLock($store, $name, $seconds, $owner);
    }

    public function acquire(): PromiseInterface
    {
        return Helpers::createFulfilledPromise($this->lock->acquire());
    }

    public function get($callback = null): PromiseInterface
    {
        return $this->lock->get($callback);
    }

    public function release(): PromiseInterface
    {
        return Helpers::createFulfilledPromise($this->lock->release());
    }
}
