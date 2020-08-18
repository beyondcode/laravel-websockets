---
title: Starting the WebSocket server
order: 2
---

# Starting the WebSocket server

Once you have configured your WebSocket apps and Pusher settings, you can start the Laravel WebSocket server by issuing the artisan command:

```bash
php artisan websockets:serve
```

## Using a different port

The default port of the Laravel WebSocket server is `6001`. You may pass a different port to the command using the `--port` option.

```bash
php artisan websockets:serve --port=3030
```

This will start listening on port `3030`.

## Restricting the listening host

By default, the Laravel WebSocket server will listen on `0.0.0.0` and will allow incoming connections from all networks. If you want to restrict this, you can start the server with a `--host` option, followed by an IP.

For example, by using `127.0.0.1`, you will only allow WebSocket connections from localhost.

```bash
php artisan websockets:serve --host=127.0.0.1
```
