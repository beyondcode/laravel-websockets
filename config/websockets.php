<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | Laravel Websockets come with a simplistic but handy dashboard for saving
    | statistics about your Servers. You can configure the dashboard settings
    | and access here, along with the middleware to execute on these routes.
    |
    */

    'dashboard' => [
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
        'domain' => env('LARAVEL_WEBSOCKETS_DOMAIN'),
        'path' => env('LARAVEL_WEBSOCKETS_PATH', 'websockets/dashboard'),
        'middleware' => ['web', 'can:view websockets dashboard']
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Manager
    |--------------------------------------------------------------------------
    |
    | Each Server contains different Application IDs, which are boxes where the
    | connections land on. This allows the  Server to separate connections from
    | other boxes. By default, this comes with a simplistic manager that uses this config "apps" array.
    |
    | You can use this to your advantage, for example, to use a Manager based
    | on a database, or even a separate Redis store. Small applications may not need to change this.
    |
    */

    'managers' => [
        'app' => \BeyondCode\LaravelWebSockets\Apps\ConfigAppManager::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Applications Repository
    |--------------------------------------------------------------------------
    |
    | If you're using the default App Manager, here are each app definition. As
    | you can see you can add multiple apps to handle connections differently.
    | For example, one app for chat, and another for video and file uploads.
    |
    | By default, the only allowed app is the one you define with
    | your PUSHER_* variables from .env.
    | You can configure to use multiple apps if you need to, or use
    | a custom App Manager that will handle the apps from a database, per se.
    |
    | You can apply multiple settings, like the maximum capacity, enable
    | client-to-client messages or statistics.
    |
    */

    'apps' => [
        'laravel' => [
            'id' => env('PUSHER_APP_ID'),
            'host' => env('PUSHER_APP_HOST'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'allow_client_messages' => false,
            'enable_statistics' => true,
            'allowed_origins' => [
                env('LARAVEL_WEBSOCKETS_DOMAIN', '0.0.0.0'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Replication PubSub
    |--------------------------------------------------------------------------
    |
    | You can enable replication to publish and subscribe to messages across the driver.
    |
    | By default, it is set to 'local', but you can configure it to use drivers
    | like Redis to ensure connection between multiple instances of
    | WebSocket servers. Just set the driver to 'redis' to enable the PubSub using Redis.
    |
    */

    'replication' => [

        'mode' => env('WEBSOCKETS_REPLICATION_MODE', 'local'),

        'modes' => [

            /*
            |--------------------------------------------------------------------------
            | Local Replication
            |--------------------------------------------------------------------------
            |
            | Local replication is actually a null replicator, meaning that it
            | is the default behaviour of storing the connections into an array.
            |
            */

            'local' => [

                /*
                |--------------------------------------------------------------------------
                | Channel Manager
                |--------------------------------------------------------------------------
                |
                | The channel manager is responsible for storing, tracking and retrieving
                | the channels as long as their members and connections.
                |
                */

                'channel_manager' => \BeyondCode\LaravelWebSockets\ChannelManagers\LocalChannelManager::class,

                /*
                |--------------------------------------------------------------------------
                | Statistics Collector
                |--------------------------------------------------------------------------
                |
                | The Statistics Collector will, by default, handle the incoming statistics,
                | storing them until they will become dumped into another database, usually
                | a MySQL database or a time-series database.
                |
                */

                'collector' => \BeyondCode\LaravelWebSockets\Statistics\Collectors\MemoryCollector::class,

            ],

            'redis' => [

                'connection' => env('WEBSOCKETS_REDIS_REPLICATION_CONNECTION', 'default'),

                /*
                |--------------------------------------------------------------------------
                | Channel Manager
                |--------------------------------------------------------------------------
                |
                | The channel manager is responsible for storing, tracking and retrieving
                | the channels as long as their members and connections.
                |
                */

                'channel_manager' => \BeyondCode\LaravelWebSockets\ChannelManagers\RedisChannelManager::class,

                /*
                |--------------------------------------------------------------------------
                | Statistics Collector
                |--------------------------------------------------------------------------
                |
                | The Statistics Collector will, by default, handle the incoming statistics,
                | storing them until they will become dumped into another database, usually
                | a MySQL database or a time-series database.
                |
                */

                'collector' => \BeyondCode\LaravelWebSockets\Statistics\Collectors\RedisCollector::class,

            ],

        ],

    ],

    'statistics' => [

        /*
        |--------------------------------------------------------------------------
        | Statistics Store
        |--------------------------------------------------------------------------
        |
        | The Statistics Store is the place where all the temporary stats will
        | be dumped. This is a much reliable store and will be used to display
        | graphs or handle it later on your app.
        |
        */

        'store' => \BeyondCode\LaravelWebSockets\Statistics\Stores\DatabaseStore::class,

        /*
        |--------------------------------------------------------------------------
        | Statistics Interval Period
        |--------------------------------------------------------------------------
        |
        | Here you can specify the interval in seconds at which
        | statistics should be logged.
        |
        */

        'interval_in_seconds' => 60,

        /*
        |--------------------------------------------------------------------------
        | Statistics Deletion Period
        |--------------------------------------------------------------------------
        |
        | When the clean-command is executed, all recorded statistics older than
        | the number of days specified here will be deleted.
        |
        */

        'delete_statistics_older_than_days' => 60,

    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Request Size
    |--------------------------------------------------------------------------
    |
    | The maximum request size in kilobytes that is allowed for an incoming
    | WebSocket request on this Server.
    |
    */

    'max_request_size_in_kb' => 256,

    /*
    |--------------------------------------------------------------------------
    | SSL Configuration
    |--------------------------------------------------------------------------
    |
    | By default, the configuration allows only on HTTP. For SSL, you need
    | to set up the the certificate, the key, and optionally, the passphrase
    | for the private key.
    | You will need to restart the server for the settings to take place.
    |
    */

    'ssl' => [
        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT'),
        'capath' => env('LARAVEL_WEBSOCKETS_SSL_CA'),
        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK'),
        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE'),
        'verify_peer' => env('APP_ENV') === 'production',
        'allow_self_signed' => env('APP_ENV') !== 'production',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Handlers
    |--------------------------------------------------------------------------
    |
    | Here you can specify the route handlers that will take over
    | the incoming/outgoing websocket connections. You can extend the
    | original class and implement your own logic, alongside
    | with the existing logic.
    |
    */

    'handlers' => [
//        'websocket' => \BeyondCode\LaravelWebSockets\Server\WebSocketHandler::class,
//        'health' => \BeyondCode\LaravelWebSockets\Server\HealthHandler::class,
//        'trigger_event' => \BeyondCode\LaravelWebSockets\API\TriggerEvent::class,
//        'fetch_channels' => \BeyondCode\LaravelWebSockets\API\FetchChannels::class,
//        'fetch_channel' => \BeyondCode\LaravelWebSockets\API\FetchChannel::class,
//        'fetch_users' => \BeyondCode\LaravelWebSockets\API\FetchUsers::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Promise Resolver
    |--------------------------------------------------------------------------
    |
    | The promise resolver is a class that takes a input value and is
    | able to make sure the PHP code runs async by using ->then(). You can
    | use your own Promise Resolver. This is usually changed when you want to
    | intercept values by the promises throughout the app, like in testing
    | to switch from async to sync.
    |
    */

    'promise_resolver' => \Amp\Success::class,

];
