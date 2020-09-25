---
title: Redis Mode
order: 2
---

# Redis Mode

Redis has the powerful ability to act both as a key-value store and as a PubSub service. This way, the connected servers will communicate between them whenever a message hits the server, so you can scale out to any amount of servers while preserving the WebSockets functionalities.

## Configure Redis mode

To enable the replication, simply change the `replication.mode` name in the `websockets.php` file to `redis`:

```php
'replication' => [

    'mode' => 'redis',

    ...

],
```

You can set the connection name to the Redis database under `redis`:

```php
'replication' => [

    'modes' =>

        'redis' => [

            'connection' => 'default',

        ],

    ],

],
```

The connections can be found in your `config/database.php` file, under the `redis` key.

## Async Redis Queue

The default Redis connection also interacts with the queues. Since you might want to dispatch jobs on Redis from the server, you can encounter an anti-pattern of using a blocking I/O connection (like PhpRedis or PRedis) within the WebSockets server.

To solve this issue, you can configure the built-in queue driver that uses the Async Redis connection when it's possible, like within the WebSockets server. It's highly recommended to switch your queue to it if you are going to use the queues within the server controllers, for example.

Add the `async-redis` queue driver to your list of connections. The configuration parameters are compatible with the default `redis` driver:

```php
'connections' => [
    'async-redis' => [
        'driver' => 'async-redis',
        'connection' => env('WEBSOCKETS_REDIS_REPLICATION_CONNECTION', 'default'),
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
]
```

Also, make sure that the default queue driver is set to `async-redis`:

```
QUEUE_CONNECTION=async-redis
```
