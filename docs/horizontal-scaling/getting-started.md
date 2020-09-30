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

To enable the replication, simply change the `replication.mode` name in the `websockets.php` file:

```php
'replication' => [

    'mode' => 'redis',

    ...

],
```

Now, when your app broadcasts the message, it will make sure the connection reaches other servers which are under the same load balancer.

The available drivers for replication are:

- [Redis](redis)
