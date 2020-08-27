---
title: Redis
order: 2
---

## Configure the Redis driver

To enable the replication, simply change the `replication.driver` name in the `websockets.php` file to `redis`:

```php
'replication' => [

    'driver' => 'redis',

    ...

],
```

You can set the connection name to the Redis database under `redis`:

```php
'replication' => [

    ...

    'redis' => [

        'connection' => 'default',

    ],

],
```

The connections can be found in your `config/database.php` file, under the `redis` key. It defaults to connection `default`.
