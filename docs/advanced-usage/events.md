---
title: Triggered Events
order: 4
---

# Triggered Events

When an user subscribes or unsubscribes from a channel, a Laravel event gets triggered.

- Connection subscribed channel: `\BeyondCode\LaravelWebSockets\Events\Subscribed`
- Connection left channel: `\BeyondCode\LaravelWebSockets\Events\Unsubscribed`

You can listen to them by [registering them in the EventServiceProvider](https://laravel.com/docs/7.x/events#registering-events-and-listeners) and attaching Listeners to them.

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'BeyondCode\LaravelWebSockets\Events\Subscribed' => [
        'App\Listeners\SomeListener',
    ],
];
```

You will be provided the connection and the channel name through the event:

```php
class SomeListener
{
    public function handle($event)
    {
        // You can access:
        // $event->connection
        // $event->channelName

        // You can also retrieve the app:
        $app = $event->connection->app;

        // Or the socket ID:
        $socketId = $event->connection->socketId;
    }
}
```
