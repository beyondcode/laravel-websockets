<?php

namespace BeyondCode\LaravelWebSockets\Cache;

use Illuminate\Support\Str;
use React\Promise\PromiseInterface;

abstract class Lock
{
    /**
     * The name of the lock.
     *
     * @var string
     */
    protected $name;

    /**
     * The number of seconds the lock should be maintained.
     *
     * @var int
     */
    protected $seconds;

    /**
     * The scope identifier of this lock.
     *
     * @var string
     */
    protected $owner;

    public function __construct($name, $seconds, $owner = null)
    {
        if (is_null($owner)) {
            $owner = Str::random();
        }
        $this->name = $name;
        $this->seconds = $seconds;
        $this->owner = $owner;
    }

    abstract public function acquire(): PromiseInterface;

    abstract public function get($callback = null): PromiseInterface;

    abstract public function release(): PromiseInterface;
}
