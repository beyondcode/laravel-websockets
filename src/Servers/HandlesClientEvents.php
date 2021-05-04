<?php

namespace BeyondCode\LaravelWebSockets\Servers;

use BeyondCode\LaravelWebSockets\Contracts\Message;
use RuntimeException;

trait HandlesClientEvents
{
    /**
     * Registers a callable to execute at a given event.
     *
     * @param  string  $event
     * @param  callable<self>  $callback
     *
     * @return void
     */
    public function onEvent(string $event, callable $callback): void
    {
        if (!array_key_exists($event, static::EVENTS)) {
            throw new RuntimeException(
                "The event [$event] is not a valid event name. Try: ", implode(', ', array_keys(static::EVENTS))
            );
        }

        $this->events[$event][] = $callback;
    }


    /**
     * Fire on closing callbacks.
     *
     * @param  int  $code
     * @param  string  $reason
     *
     * @return void
     */
    protected function fireOnClosingEvents(int $code, string $reason): void
    {
        foreach ($this->events['onClosingEvents'] as $callback) {
            $callback($this, $code, $reason);
        }
    }

    /**
     * Fire on closed callbacks.
     *
     * @param  int  $code
     * @param  string  $reason
     *
     * @return void
     */
    protected function fireOnClosedEvents(int $code, string $reason): void
    {
        foreach ($this->events['onClosedEvents'] as $callback) {
            $callback($this, $code, $reason);
        }
    }

    /**
     * Fire on received callbacks.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $incoming
     *
     * @return void
     */
    protected function fireOnReceivedEvents(Message $incoming): void
    {
        foreach ($this->events['onReceivedEvents'] as $callback) {
            $callback($this, $incoming);
        }
    }

    /**
     * Fire on receiving callbacks.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $incoming
     *
     * @return void
     */
    protected function fireOnReceivingEvents(Message $incoming): void
    {
        foreach ($this->events['onReceivingEvents'] as $callback) {
            $callback($this, $incoming);
        }
    }

    /**
     * Fire on sent callbacks.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $outgoing
     *
     * @return void
     */
    protected function fireOnSentEvents(Message $outgoing): void
    {
        foreach ($this->events['onSentEvents'] as $callback) {
            $callback($this, $outgoing);
        }
    }

    /**
     * Fire on sending callbacks.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $outgoing
     *
     * @return void
     */
    protected function fireOnSendingEvents(Message $outgoing): void
    {
        foreach ($this->events['onSendingEvents'] as $callback) {
            $callback($this, $outgoing);
        }
    }

}
