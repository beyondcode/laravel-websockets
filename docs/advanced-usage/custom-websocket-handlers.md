# Custom WebSocket Handlers

While this package's main purpose is to make the usage of either the Pusher JavaScript client or Laravel Echo as easy as possible, you are not limited to the Pusher protocol at all. 
There might be situations where all you need is a simple, bare-bone, WebSocket server where you want to have full control over the incoming payload and what you want to do with it - without having "channels" in the way.

You can easily create your own custom WebSocketHandler class. All you need to do is implement Ratchets `Ratchet\WebSocket\MessageComponentInterface`.

Once implemented, you will have a class that looks something like this:

```php
namespace App;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class MyCustomWebSocketHandler implements MessageComponentInterface
{

    public function onOpen(ConnectionInterface $connection)
    {
        // TODO: Implement onOpen() method.
    }
    
    public function onClose(ConnectionInterface $connection)
    {
        // TODO: Implement onClose() method.
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $msg)
    {
        // TODO: Implement onMessage() method.
    }
}
```

In the class itself you have full control over all the lifecycle events of your WebSocket connections and can intercept the incoming messages and react to them.

The only part missing is, that you will need to tell our WebSocket server to load this handler at a specific route endpoint. This can be achieved using the `WebSocketsRouter` facade.

This class takes care of registering the routes with the actual webSocket server. You can use the `webSocket` method to define a custom WebSocket endpoint. The method needs two arguments: the path where the WebSocket handled should be available and the fully qualified classname of the WebSocket handler class.

This could, for example, be done inside your `routes/web.php` file.

```php
WebSocketsRouter::webSocket('/my-websocket', \App\MyCustomWebSocketHandler::class);
```

Once you've added the custom WebSocket route, be sure to restart our WebSocket server for the changes to take place.