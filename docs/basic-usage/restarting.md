---
title: Restarting Server
order: 4
---

# Restarting Server

If you use Supervisor to keep your server alive, you might want to restart it just like `queue:restart` does.

To do so, consider using the `websockets:restart`. In a maximum of 10 seconds since issuing the command, the server will be restarted.

```bash
php artisan websockets:restart
```
