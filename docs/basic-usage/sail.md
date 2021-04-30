---
title: Laravel Sail
order: 5
---

# Run in Laravel Sail

To be able to use Laravel Websockets in Sail, you should just forward the port:

```yaml
# For more information: https://laravel.com/docs/sail
version: '3'
services:
    laravel.test:
        build:
            context: ./vendor/laravel/sail/runtimes/8.0
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP}'
        image: sail-8.0/app
        ports:
            - '${APP_PORT:-80}:80'
            - '${LARAVEL_WEBSOCKETS_PORT:-6001}:${LARAVEL_WEBSOCKETS_PORT:-6001}'
```
