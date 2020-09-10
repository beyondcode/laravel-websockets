<?php

namespace BeyondCode\LaravelWebSockets\Test\Mocks;

use Clue\React\Block;
use React\Promise\PromiseInterface;
use React\Promise\FulfilledPromise;

class PromiseResolver implements PromiseInterface
{
    /**
     * The promise to resolve.
     *
     * @var \React\Promise\PromiseInterface
     */
    protected $promise;

    /**
     * The loop.
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * Initialize the promise resolver.
     *
     * @param  PromiseInterface  $promise
     * @param  LoopInterface  $loop
     * @return void
     */
    public function __construct($promise, $loop)
    {
        $this->promise = $promise;
        $this->loop = $loop;
    }

    /**
     * Intercept the promise then() and run it in sync.
     *
     * @param  callable|null  $onFulfilled
     * @param  callable|null  $onRejected
     * @param  callable|null  $onProgress
     * @return PromiseInterface
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        $result = Block\await(
            $this->promise, $this->loop
        );

        $result = call_user_func($onFulfilled, $result);

        return $result instanceof PromiseInterface
            ? $result
            : new FulfilledPromise($result);
    }

    /**
     * Pass the calls to the promise.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func([$this->promise, $method], $args);
    }
}
