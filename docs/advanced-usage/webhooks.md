---
title: Webhooks
order: 3
---

# Webhooks

While you can create any custom websocket handlers, you might still want to intercept and run your own custom business logic on each websocket connection.

In Pusher, there are [Pusher Webhooks](https://pusher.com/docs/channels/server_api/webhooks) that do this job. However, since the implementation is a pure controller,
you might want to extend it and update the config file to reflect the changes:

For example, running your own business logic on connection open and close:

```php
namespace App\Controllers\WebSockets;

use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler as BaseWebSocketHandler;
use Ratchet\ConnectionInterface;

class WebSocketHandler extends BaseWebSocketHandler
{
    public function onOpen(ConnectionInterface $connection)
    {
        parent::onOpen($connection);

        // Run code on open
        // $connection->app contains the app details
        // $this->channelManager is accessible
    }

    public function onClose(ConnectionInterface $connection)
    {
        parent::onClose($connection);

        // Run code on close.
        // $connection->app contains the app details
        // $this->channelManager is accessible
    }
}
```

Once you implemented it, replace the `handlers.websocket` class name in config:

```php
'handlers' => [

    'websocket' => App\Controllers\WebSockets\WebSocketHandler::class,

],
```

A server restart is required afterwards.
