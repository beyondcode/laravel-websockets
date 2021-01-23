---
title: Dispatched Events
order: 5
---

# Dispatched Events

Laravel WebSockets takes advantage of Laravel's Event dispatching observer, in a way that you can handle in-server events outside of it.

For example, you can listen for events like when a new connection establishes or when an user joins a presence channel.

## Events

Below you will find a list of dispatched events:

- `BeyondCode\LaravelWebSockets\Events\NewConnection` - when a connection successfully establishes on the server
- `BeyondCode\LaravelWebSockets\Events\ConnectionClosed` - when a connection leaves the server
- `BeyondCode\LaravelWebSockets\Events\SubscribedToChannel` - when a connection subscribes to a specific channel
- `BeyondCode\LaravelWebSockets\Events\UnsubscribedFromChannel` - when a connection unsubscribes from a specific channel
- `BeyondCode\LaravelWebSockets\Events\WebSocketMessageReceived` - when the server receives a message
- `BeyondCode\LaravelWebSockets\EventsConnectionPonged` - when a connection pings to the server that it is still alive

## Queued Listeners

Because the default Redis connection (either PhpRedis or Predis) is a blocking I/O method and can cause problems with the server speed and availability, you might want to check the [Non-Blocking Queue Driver](non-blocking-queue-driver.md) documentation that helps you create the Async Redis queue driver that is going to fix the Blocking I/O issue.

If set up, you can use the `async-redis` queue driver in your listeners:

```php
<?php

namespace App\Listeners;

use BeyondCode\LaravelWebSockets\Events\NewConnection;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleNewConnections implements ShouldQueue
{
    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public $connection = 'async-redis';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NewConnection  $event
     * @return void
     */
    public function handle(NewConnection $event)
    {
        //
    }
}
```

The `EventServiceProvider` might look like this, registering the listeners that are going to be used by the event dispatching:

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    \BeyondCode\LaravelWebSockets\Events\NewConnection::class => [
        App\Listeners\HandleNewConnections::class,
    ],
];
```
