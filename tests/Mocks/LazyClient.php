<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use Clue\React\Redis\LazyClient as BaseLazyClient;
use PHPUnit\Framework\Assert as PHPUnit;

class LazyClient extends BaseLazyClient
{
    /**
     * A list of called methods for the connector.
     *
     * @var array
     */
    protected $calls = [];

    /**
     * {@inheritdoc}
     */
    public function __call($name, $args)
    {
        $this->calls[] = [$name, $args];

        return parent::__call($name, $args);
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
     * Get the list of all calls.
     *
     * @return array
     */
    public function getCalledFunctions()
    {
        return $this->calls;
    }
}
