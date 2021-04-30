---
title: Non-Blocking Queue Driver
order: 4
---

# Non-Blocking Queue Driver

In Laravel, he default Redis connection also interacts with the queues. Since you might want to dispatch jobs on Redis from the server, you can encounter an anti-pattern of using a blocking I/O connection (like PhpRedis or PRedis) within the WebSockets server.

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
