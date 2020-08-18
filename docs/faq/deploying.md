---
title: Deploying
order: 1
---

# Deploying

When your application is ready to get deployed, here are some tips to improve your WebSocket server.

### Open Connection Limit

On Unix systems, every user that connects to your WebSocket server is represented as a file somewhere on the system.
As a security measurement of every Unix based OS, the number of "file descriptors" an application may have open at a time is limited - most of the time to a default value of 1024 - which would result in a maximum number of 1024 concurrent users on your WebSocket server.

In addition to the OS restrictions, this package makes use of an event loop called "stream_select", which has a hard limit of 1024.

#### Increasing the maximum number of file descriptors

The operating system limit of open "file descriptors" can be increased using the `ulimit` command. The `-n` option modifies the number of open file descriptors.

```bash
ulimit -n 10000
```

The `ulimit` command only **temporarily** increases the maximum number of open file descriptors. To permanently modify this value, you can edit it in your operating system `limits.conf` file.

You are best to do so by creating a file in the `limits.d` directory. This will work for both Red Hat & Ubuntu derivatives.

```bash
$ cat /etc/security/limits.d/laravel-echo.conf
laravel-echo		soft		nofile		10000
```

The above example assumes you will run your echo server as the `laravel-echo` user, you are free to change that to your liking.

#### Changing the event loop

To make use of a different event loop, that does not have a hard limit of 1024 concurrent connections, you can either install the `ev` or `event` PECL extension using:

```bash
sudo pecl install ev
# or
sudo pecl install event
```

#### Deploying on Laravel Forge

If your are using [Laravel Forge](https://forge.laravel.com/) for the deployment [this article by Alex Bouma](https://alex.bouma.dev/installing-laravel-websockets-on-forge) might help you out.

#### Deploying on Laravel Vapor

Since [Laravel Vapor](https://vapor.laravel.com) runs on a serverless architecture, you will need to spin up an actual EC2 Instance that runs in the same VPC as the Lambda function to be able to make use of the WebSocket connection.

The Lambda function will make sure your HTTP request gets fulfilled, then the EC2 Instance will be continuously polled through the WebSocket protocol.

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

Please note that, by default, just like file descriptiors,  `supervisor` will force a maximum number of open files onto all the processes that it manages. This is configured by the `minfds` parameter in `supervisord.conf`.

If you want to increase the maximum number of open files, you may do so in `/etc/supervisor/supervisord.conf` (Debian/Ubuntu) or `/etc/supervisord.conf` (Red Hat/CentOS):

```
[supervisord]
minfds=10240; (min. avail startup file descriptors;default 1024)
```

After changing this setting, you'll need to restart the supervisor process (which in turn will restart all your processes that it manages).

## Debugging supervisor

If you run into issues with Supervisor, like not supporting a lot of connections, consider checking the [Ratched docs on deploying with Supervisor](http://socketo.me/docs/deploy#supervisor).
