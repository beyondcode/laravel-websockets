# Laravel WebSockets 🛰

[![Latest Version on Packagist](https://img.shields.io/packagist/v/beyondcode/laravel-websockets.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-websockets)
[![Build Status](https://img.shields.io/travis/beyondcode/laravel-websockets/master.svg?style=flat-square)](https://travis-ci.org/beyondcode/laravel-websockets)
[![Quality Score](https://img.shields.io/scrutinizer/g/beyondcode/laravel-websockets.svg?style=flat-square)](https://scrutinizer-ci.com/g/beyondcode/laravel-websockets)
[![Total Downloads](https://img.shields.io/packagist/dt/beyondcode/laravel-websockets.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-websockets)

Bring the power of WebSockets to your Laravel application. Drop-in Pusher replacement, SSL support, Laravel Echo support and a debug dashboard are just some of it's features.

## Installation

You can install the package via composer:

```bash
composer require beyondcode/laravel-websockets
```

The package will automatically register a service provider.

## Usage

Once the package is installed, you can publish the configuration file using:

```
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

To start the WebSocket server, use:

```
php artisan websockets:serve
```

For in-depth usage and deployment details, please take a look at the [official documentation](https://docs.beyondco.de/laravel-websockets/).

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email marcel@beyondco.de instead of using the issue tracker.

## Credits

- [Marcel Pociot](https://github.com/mpociot)
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
