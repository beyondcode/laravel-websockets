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

## Keeping the socket server running with supervisord

The `websockets:serve` daemon needs to always be running in order to accept connections. This is a prime use case for `supervisor`, a task runner on Linux.

First, make sure `supervisor` is installed.

```bash
# On Debian / Ubuntu
apt install supervisor

# On Red Hat / CentOS
yum install supervisor
systemctl enable supervisord
```

Once installed, add a new process that `supervisor` needs to keep running. You place your configurations in the `/etc/supervisor/conf.d` (Debian/Ubuntu) or `/etc/supervisord.d` (Red Hat/CentOS) directory.

Within that directory, create a new file called `websockets.conf`.

```bash
[program:websockets]
command=/usr/bin/php /home/laravel-echo/laravel-websockets/artisan websockets:serve
numprocs=1
autostart=true
autorestart=true
user=laravel-echo
```

Once created, instruct `supervisor` to reload its configuration files (without impacting the already running `supervisor` jobs).

```bash
supervisorctl update
supervisorctl start websockets
```

Your echo server should now be running (you can verify this with `supervisorctl status`). If it were to crash, `supervisor` will automatically restart it.

Please note that, by default, `supervisor` will force a maximum number of open files onto all the processes that it manages. This is configured by the `minfds` parameter in `supervisord.conf`.

If you want to increase the maximum number of open files, you may do so in `/etc/supervisor/supervisord.conf` (Debian/Ubuntu) or `/etc/supervisord.conf` (Red Hat/CentOS):

```
[supervisord]
minfds=10240; (min. avail startup file descriptors;default 1024)
```

After changing this setting, you'll need to restart the supervisor process (which in turn will restart all your processes that it manages).
