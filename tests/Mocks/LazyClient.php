<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use Clue\React\Redis\Factory;
use Clue\React\Redis\LazyClient as BaseLazyClient;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Assert as PHPUnit;
use React\EventLoop\LoopInterface;

class LazyClient extends BaseLazyClient
{
    /**
     * A list of called methods for the connector.
     *
     * @var array
     */
    protected $calls = [];

    /**
     * A list of called events for the connector.
     *
     * @var array
     */
    protected $events = [];

    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * {@inheritdoc}
     */
    public function __construct($target, Factory $factory, LoopInterface $loop)
    {
        parent::__construct($target, $factory, $loop);

        $this->redis = Redis::connection();
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $args)
    {
        $this->calls[] = [$name, $args];

        if (! in_array($name, ['subscribe', 'psubscribe', 'unsubscribe', 'punsubscribe', 'onMessage'])) {
            $this->redis->__call($name, $args);
        }

        return parent::__call($name, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function on($event, callable $listener)
    {
        $this->events[] = $event;

        return parent::on($event, $listener);
    }

    /**
     * Check if the method got called.
     *
     * @param  string  $name
     * @return $this
     */
    public function assertCalled($name)
    {
        foreach ($this->getCalledFunctions() as $function) {
            [$calledName, ] = $function;

            if ($calledName === $name) {
                PHPUnit::assertTrue(true);

                return $this;
            }
        }

        PHPUnit::assertFalse(true);

        return $this;
    }

    /**
     * Check if the method with args got called.
     *
     * @param  string  $name
     * @param  array  $args
     * @return $this
     */
    public function assertCalledWithArgs($name, array $args)
    {
        foreach ($this->getCalledFunctions() as $function) {
            [$calledName, $calledArgs] = $function;

            if ($calledName === $name && $calledArgs === $args) {
                PHPUnit::assertTrue(true);

                return $this;
            }
        }

        PHPUnit::assertFalse(true);

        return $this;
    }

    /**
     * Check if the method with args got called an amount of times.
     *
     * @param  string  $name
     * @param  array  $args
     * @return $this
     */
    public function assertCalledWithArgsCount($times = 1, $name, array $args)
    {
        $total = collect($this->getCalledFunctions())->filter(function ($function) use ($name, $args) {
            [$calledName, $calledArgs] = $function;

            return $calledName === $name && $calledArgs === $args;
        });

        PHPUnit::assertCount($times, $total);

        return $this;
    }

    /**
     * Check if the method didn't call.
     *
     * @param  string  $name
     * @return $this
     */
    public function assertNotCalled($name)
    {
        foreach ($this->getCalledFunctions() as $function) {
            [$calledName, ] = $function;

            if ($calledName === $name) {
                PHPUnit::assertFalse(true);

                return $this;
            }
        }

        PHPUnit::assertTrue(true);

        return $this;
    }

    /**
     * Check if the method got not called with specific args.
     *
     * @param  string  $name
     * @param  array  $args
     * @return $this
     */
    public function assertNotCalledWithArgs($name, array $args)
    {
        foreach ($this->getCalledFunctions() as $function) {
            [$calledName, $calledArgs] = $function;

            if ($calledName === $name && $calledArgs === $args) {
                PHPUnit::assertFalse(true);

                return $this;
            }
        }

        PHPUnit::assertTrue(true);

        return $this;
    }

    /**
     * Check if the method with args got called an amount of times.
     *
     * @param  string  $name
     * @param  array  $args
     * @return $this
     */
    public function assertNotCalledWithArgsCount($times = 1, $name, array $args)
    {
        $total = collect($this->getCalledFunctions())->filter(function ($function) use ($name, $args) {
            [$calledName, $calledArgs] = $function;

            return $calledName === $name && $calledArgs === $args;
        });

        PHPUnit::assertNotCount($times, $total);

        return $this;
    }

    /**
     * Check if no function got called.
     *
     * @return $this
     */
    public function assertNothingCalled()
    {
        PHPUnit::assertEquals([], $this->getCalledFunctions());

        return $this;
    }

    /**
     * Check if the event got dispatched.
     *
     * @param  string  $event
     * @return $this
     */
    public function assertEventDispatched($event)
    {
        foreach ($this->getCalledEvents() as $dispatchedEvent) {
            if ($dispatchedEvent === $event) {
                PHPUnit::assertTrue(true);

                return $this;
            }
        }

        PHPUnit::assertFalse(true);

        return $this;
    }

    /**
     * Check if no function got called.
     *
     * @return $this
     */
    public function assertNothingDispatched()
    {
        PHPUnit::assertEquals([], $this->getCalledEvents());

        return $this;
    }

    /**
     * Get the list of all calls.
     *
     * @return array
     */
    public function getCalledFunctions()
    {
        return $this->calls;
    }

    /**
     * Get the list of events.
     *
     * @return array
     */
    public function getCalledEvents()
    {
        return $this->events;
    }

    /**
     * Dump the assertions.
     *
     * @return void
     */
    public function dd()
    {
        dd([
            'functions' => $this->getCalledFunctions(),
            'events' => $this->getCalledEvents(),
        ]);
    }

    /**
     * Reset the assertions.
     *
     * @return $this
     */
    public function resetAssertions()
    {
        $this->calls = [];
        $this->events = [];

        return $this;
    }
}
