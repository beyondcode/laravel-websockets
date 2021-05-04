<?php

namespace BeyondCode\LaravelWebSockets\Loop\Drivers;

use Amp\Promise;
use BeyondCode\LaravelWebSockets\Contracts\Promise as PromiseContract;
use Throwable;

use function Amp\Promise\wait;

class AmpPromise implements PromiseContract
{
    /**
     * Underlying Amp Promise.
     *
     * @var \Amp\Promise
     */
    protected $promise;

    /**
     * Sets of callbacks to execute.
     *
     * @var array|callable<\Throwable,mixed>
     */
    protected $callbacks;

    /**
     * Promise constructor.
     *
     * @param  \Amp\Promise  $promise
     */
    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    /**
     * Saves a callback to be executed once the async call resolves.
     *
     * @param  callable<mixed>  $callable
     *
     * @return $this
     */
    public function then(callable $callable): PromiseContract
    {
        $this->callbacks[] = static function ($error, $success) use ($callable): void {
            $callable($success);
        };

        return $this;
    }

    /**
     * Catches the exception if there is one.
     *
     * @param  callable<\Throwable>  $callable
     *
     * @return $this
     */
    public function catch(callable $callable): PromiseContract
    {
        $this->callbacks[] = static function ($error) use ($callable): void {
            if ($error instanceof Throwable) {
                $callable($error);
            }
        };

        return $this;
    }

    /**
     * Waits for the value to be resolved from the async function and returns it.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function return()
    {
        foreach ($this->callbacks as $callback) {
            $this->promise->onResolve($callback);
        }

        return wait($this->promise);
    }
}
