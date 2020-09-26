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
