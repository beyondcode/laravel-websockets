<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

interface Promise
{
    /**
     * Saves a callback to be executed once the async call resolves.
     *
     * @param  callable<mixed>  $callable
     *
     * @return $this
     */
    public function then(callable $callable): self;

    /**
     * Catches the exception if there is one.
     *
     * @param  callable<\Throwable>  $callable
     *
     * @return $this
     */
    public function catch(callable $callable): self;

    /**
     * Waits for the value to be resolved from the async function and returns it.
     *
     * @return mixed
     */
    public function return();
}
