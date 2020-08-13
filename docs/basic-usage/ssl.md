---
title: SSL Support
order: 3
---

# SSL Support

Since most of the web's traffic is going through HTTPS, it's also crucial to secure your WebSocket server. Luckily, adding SSL support to this package is really simple.

## Configuration

The SSL configuration takes place in your `config/websockets.php` file.
The default configuration has a SSL section that looks like this:

```php
'ssl' => [
    /*
     * Path to local certificate file on filesystem. It must be a PEM encoded file which
     * contains your certificate and private key. It can optionally contain the
     * certificate chain of issuers. The private key also may be contained
     * in a separate file specified by local_pk.
     */
    'local_cert' => null,

    /*
     * Path to local private key file on filesystem in case of separate files for
     * certificate (local_cert) and private key.
     */
    'local_pk' => null,

    /*
     * Passphrase with which your local_cert file was encoded.
     */
    'passphrase' => null
],
```

But this is only a subset of all the available configuration options.
This packages makes use of the official PHP [SSL context options](http://php.net/manual/en/context.ssl.php).

So if you find yourself in the need of adding additional configuration settings, take a look at the PHP documentation and simply add the configuration parameters that you need.

After setting up your SSL settings, you can simply (re)start your WebSocket server using:

```bash
php artisan websockets:serve
```

## Client configuration

When your SSL settings are in place and working, you still need to tell Laravel Echo that it should make use of it.
You can do this by specifying the `forceTLS` property in your JavaScript file, like this:

```js
import Echo from "laravel-echo"

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-pusher-key',
    wsHost: window.location.hostname,
    wsPort: 6001,
    disableStats: true,
    forceTLS: true
});
```

## Server configuration

When broadcasting events from your Laravel application to the WebSocket server, you also need to tell Laravel to make use of HTTPS instead of HTTP. You can do this by setting the `scheme` option in your `config/broadcasting.php` file to `https`:

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'host' => '127.0.0.1',
        'port' => 6001,
        'scheme' => 'https'
    ],
],
```

Since the SSL configuration can vary quite a lot, depending on your setup, let's take a look at the most common approaches.

## Usage with Laravel Valet

Laravel Valet uses self-signed SSL certificates locally.
To use self-signed certificates with Laravel WebSockets, here's how the SSL configuration section in your `config/websockets.php` file should look like.

::: tip
Make sure that you replace `YOUR-USERNAME` with your Mac username and `VALET-SITE.TLD` with the host of the Valet site that you're working on right now. For example `laravel-websockets-demo.test`.
:::

```php
'ssl' => [
    /*
     * Path to local certificate file on filesystem. It must be a PEM encoded file which
     * contains your certificate and private key. It can optionally contain the
     * certificate chain of issuers. The private key also may be contained
     * in a separate file specified by local_pk.
     */
    'local_cert' => '/Users/YOUR-USERNAME/.config/valet/Certificates/VALET-SITE.TLD.crt',

    /*
     * Path to local private key file on filesystem in case of separate files for
     * certificate (local_cert) and private key.
     */
    'local_pk' => '/Users/YOUR-USERNAME/.config/valet/Certificates/VALET-SITE.TLD.key',

    /*
     * Passphrase with which your local_cert file was encoded.
     */
    'passphrase' => null,

    'verify_peer' => false,
],
```

Next, you need to adjust the `config/broadcasting.php` file to make use of a secure connection when broadcasting messages from Laravel to the WebSocket server.

You also need to disable SSL verification.

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'host' => '127.0.0.1',
        'port' => 6001,
        'scheme' => 'https',
        'curl_options' => [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]
    ],
],
```

Last but not least, you still need to configure Laravel Echo to also use WSS on port 6001.

```js
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-pusher-key',
    wsHost: window.location.hostname,
    wsPort: 6001,
    wssPort: 6001,
    disableStats: true,
});
```

## Usage with a reverse proxy (like Nginx)

Alternatively, you can also use a proxy service - like Nginx, HAProxy or Caddy - to handle the SSL configurations and proxy all requests in plain HTTP to your echo server.

A basic Nginx configuration would look like this, but you might want to tweak the SSL parameters to your liking.

```
server {
  listen        443 ssl;
  listen        [::]:443 ssl;
  server_name   socket.yourapp.tld;

  # Start the SSL configurations
  ssl                  on;
  ssl_certificate      /etc/letsencrypt/live/socket.yourapp.tld/fullchain.pem;
  ssl_certificate_key  /etc/letsencrypt/live/socket.yourapp.tld/privkey.pem;

  location / {
    proxy_pass             http://127.0.0.1:6001;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;

    # Allow the use of websockets
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
  }
}
```

You can now talk HTTPS to `socket.yourapp.tld`. You would configure your `config/broadcasting.php` like the example above, treating your socket server as an `https` endpoint.

### Same location for websockets and web contents

To have the websockets be served at the same location and port as your other web content, Nginx can be taught to map incoming requests based on their type to special sub-locations.

```
map $http_upgrade $type {
  default "web";
  websocket "ws";
}

server {
  # Your default configuration comes here...

  location / {
    try_files /nonexistent @$type;
  }
  
  location @web {
    try_files $uri $uri/ /index.php?$query_string;
  }

  location @ws {
    proxy_pass             http://127.0.0.1:6001;
    proxy_set_header Host  $host;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;

    # Allow the use of websockets
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
  }
}
```

This configuration is useful if you do not want to open multiple ports or you are restricted to which ports are already opened on your server. Alternatively, a second Nginx location can be used on the server-side, while the Pusher configuration [`wsPath`](https://github.com/pusher/pusher-js#wspath) can be used on the client-side (_note: `"pusher-js": ">=4.2.2"` is required for this configuration option_).

```
server {
  # Your default configuration comes here...

  location /ws {
    proxy_pass             http://127.0.0.1:6001;
    proxy_set_header Host  $host;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;

    # Allow the use of websockets
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
  }
}
```

### Nginx worker connections

Note that you might need to increase the amount of `worker_connections` in Nginx. Your WebSocket connections will now be sent to Nginx, which in turn will send those along to the websocket server.

By default, that will have a sane limit of 1024 connections. If you are expecting more concurrent connections to your WebSockets, you can increase this in your global `nginx.conf`.

```
events {
    worker_connections  1024;
}
```

You know you've reached this limit of your Nginx error logs contain similar messages to these:

```
[alert] 1024 worker_connections are not enough while connecting to upstream
```

Remember to restart your Nginx after you've modified the `worker_connections`.

### Example using Caddy

[Caddy](https://caddyserver.com) can also be used to automatically obtain a TLS certificate from Let's Encrypt and terminate TLS before proxying to your echo server.

An example configuration would look like this:

```
socket.yourapp.tld {
    rewrite / {
        if {>Connection} has Upgrade
        if {>Upgrade} is websocket
        to /websocket-proxy/{path}?{query}
    }

    proxy /websocket-proxy 127.0.0.1:6001 {
        without /special-websocket-url
        transparent
        websocket
    }
    
    tls youremail.com
}
```

Note the `to /websocket-proxy`, this is a dummy path to allow the `proxy` directive to only proxy on websocket connections. This should be a path that will never be used by your application's routing. Also, note that you should change `127.0.0.1` to the hostname of your websocket server. For example, if you're running in a Docker environment, this might be the container name of your websocket server.
