---
title: Getting Started
order: 1
---

When running Laravel WebSockets without additional configuration, you won't be able to scale your servers out.

For example, even with Sticky Load Balancer settings, you won't be able to keep track of your users' connections to notify them properly when messages occur if you got multiple nodes that run the same `websockets:serve` command.

The reason why this happen is because the default channel manager runs on arrays, which is not a database other instances can access.

To do so, we need a database and a way of notifying other instances when connections occur.

For example, Redis does a great job by encapsulating the both the way of notifying (Pub/Sub module) and the storage (key-value datastore).

## Configure the replication

To enable the replication, simply change the `replication.driver` name in the `websockets.php` file:

```php
'replication' => [

    'driver' => 'redis',

    ...

],
```

Now, when your app broadcasts the message, it will make sure the connection reaches other servers which are under the same load balancer.

The available drivers for replication are:

- [Redis](redis)

## Configure the Statistics driver

If you work with multi-node environments, beside replication, you shall take a look at the statistics logger. Each time your user connects, disconnects or send a message, you can track the statistics. However, these are centralized in one place before they are dumped in the database.

Unfortunately, you might end up with multiple rows when multiple servers run in parallel.

To fix this, just change the `statistics.logger` class with a logger that is able to centralize the statistics in one place. For example, you might want to store them into a Redis instance:

```php
'statistics' => [

    'logger' => \BeyondCode\LaravelWebSockets\Statistics\Logger\RedisStatisticsLogger::class,

    ...

],
```

Check the `websockets.php` config file for more details.
