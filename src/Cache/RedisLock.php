<?php

namespace BeyondCode\LaravelWebSockets\Cache;

use Clue\React\Redis\Client;
use Illuminate\Cache\LuaScripts;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class RedisLock extends Lock
{
    /**
     * The asynchronous redis client.
     *
     * @var Client
     */
    protected $redis;

    public function __construct(Client $redis, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->redis = $redis;
    }

    public function acquire(): PromiseInterface
    {
        $promise = new Deferred();

        if ($this->seconds > 0) {
            $this->redis
                ->set($this->name, $this->owner, 'EX', $this->seconds, 'NX')
                ->then(function ($result) use ($promise) {
                    $promise->resolve($result === true);
                });
        } else {
            $this->redis
                ->setnx($this->name, $this->owner)
                ->then(function ($result) use ($promise) {
                    $promise->resolve($result === 1);
                });
        }

        return $promise->promise();
    }

    public function get($callback = null): PromiseInterface
    {
        $promise = new Deferred();

        $this->acquire()
            ->then(function ($result) use ($callback, $promise) {
                if ($result) {
                    try {
                        $callback();
                    } finally {
                        $promise->resolve($this->release());
                    }
                }
            });

        return $promise->promise();
    }

    public function release(): PromiseInterface
    {
        return $this->redis->eval(LuaScripts::releaseLock(), 1, $this->name, $this->owner);
    }
}
