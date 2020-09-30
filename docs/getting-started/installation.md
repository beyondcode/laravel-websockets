---
title: Installation
order: 2
---

# Installation

Laravel WebSockets can be installed via composer:

```bash
composer require beyondcode/laravel-websockets
```

The package will automatically register a service provider.

You need to publish the WebSocket configuration file:

```bash
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

# Statistics

This package comes with migrations to store statistic information while running your WebSocket server. For more info, check the [Debug Dashboard](../debugging/dashboard.md) section.

You can publish the migration file using:

```bash
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
```

Run the migrations with:

```bash
php artisan migrate
```
