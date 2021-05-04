<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Illuminate\Support\Collection;

/**
 * This interface abstracts the Client and its socket connection to the
 * WebSocket Server, as each server has their own way of interact with
 * their clients, broadcast messages, and receive messages from them.
 */
interface Client
{
    /**
     * Default array for events.
     *
     * @var array<array<callable<static,\BeyondCode\LaravelWebSockets\Contracts\Message>|callable<static,int,string>>>
     */
    public const EVENTS = [
        'onClosing'   => [],
        'onClosed'    => [],
        'onReceived'  => [],
        'onReceiving' => [],
        'onSent'      => [],
        'onSending'   => [],
    ];

    /**
     * Returns the underlying client or connection implementation.
     *
     * @return mixed
     */
    public function getClient();

    /**
     * Returns the unique connection ID (socket ID) on a server.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Returns multiple messages using a Generator-style.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Generator<\BeyondCode\LaravelWebSockets\Contracts\Message>>
     */
    public function receive(): Promise;

    /**
     * Sends data to the connected client.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $message
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool>  Returns `true` if it was delivered, or `false` if
     *     the connection was closed.
     */
    public function send(Message $message): Promise;

    /**
     * Send a "ping" to the client to check if it's alive.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool>
     */
    public function ping(): Promise;

    /**
     * Checks if the last heartbeat was before a given amount of seconds.
     *
     * @param  int  $seconds
     *
     * @return bool
     */
    public function isMissing(int $seconds): bool;

    /**
     * Returns app-driven metadata associated with this Client.
     *
     * @return \Illuminate\Support\Collection<string>
     */
    public function metadata(): Collection;

    /**
     * Closes the connection, sending a last message to the client.
     *
     * @param  int  $code
     * @param  string  $reason
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<null>  It just returns when the connection closed
     */
    public function close(int $code = 1000, string $reason = ''): Promise;

    /**
     * Registers a callable to execute at a given event.
     *
     * @param  string  $event
     * @param  callable<self>  $callback
     *
     * @return void
     */
    public function onEvent(string $event, callable $callback): void;
}
