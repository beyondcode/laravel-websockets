---
title: Cloudflare
order: 3
---

# Cloudflare

In some cases, you might use Cloudflare and notice that your production server does not seem to respond to your `:6001` port.

This is because Cloudflare does not seem to open ports, [excepting a few of them](https://blog.cloudflare.com/cloudflare-now-supporting-more-ports/).

To mitigate this issue, for example, you can run your server on port `2096`:

```bash
php artisan websockets:serve --port=2096
```

You will notice that the new `:2096` websockets server will work properly.
