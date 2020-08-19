<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | You can configure the dashboard settings from here.
    |
    */

    'dashboard' => [

        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),

        'path' => 'laravel-websockets',

        'middleware' => [
            'web',
            \BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize::class,
        ],

    ],

    'managers' => [

        /*
        |--------------------------------------------------------------------------
        | Application Manager
        |--------------------------------------------------------------------------
        |
        | An Application manager determines how your websocket server allows
        | the use of the TCP protocol based on, for example, a list of allowed
        | applications.
        | By default, it uses the defined array in the config file, but you can
        | anytime implement the same interface as the class and add your own
        | custom method to retrieve the apps.
        |
        */

        'app' => \BeyondCode\LaravelWebSockets\Apps\ConfigAppManager::class,

        /*
        |--------------------------------------------------------------------------
        | Channel Manager
        |--------------------------------------------------------------------------
        |
        | When users subscribe or unsubscribe from specific channels,
        | the connections are stored to keep track of any interaction with the
        | WebSocket server.
        | You can however add your own implementation that will help the store
        | of the channels alongside their connections.
        |
        */

        'channel' => \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Applications Repository
    |--------------------------------------------------------------------------
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
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'enable_client_messages' => false,
            'enable_statistics' => true,
            'allowed_origins' => [
                //
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Request Size
    |--------------------------------------------------------------------------
    |
    | The maximum request size in kilobytes that is allowed for
    | an incoming WebSocket request.
    |
    */

    'max_request_size_in_kb' => 250,

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

        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),

        'capath' => env('LARAVEL_WEBSOCKETS_SSL_CA', null),

        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),

        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE', null),

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

        'websocket' => \BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Replication PubSub
    |--------------------------------------------------------------------------
    |
    | You can enable replication to publish and subscribe to
    | messages across the driver.
    |
    | By default, it is set to 'local', but you can configure it to use drivers
    | like Redis to ensure connection between multiple instances of
    | WebSocket servers. Just set the driver to 'redis' to enable the PubSub using Redis.
    |
    */

    'replication' => [

        'driver' => 'local',

        'redis' => [

            'connection' => 'default',

        ],

    ],

    'statistics' => [

        /*
        |--------------------------------------------------------------------------
        | Statistics Driver
        |--------------------------------------------------------------------------
        |
        | Here you can specify which driver to use to store the statistics to.
        | See down below for each driver's setting.
        |
        | Available: database
        |
        */

        'driver' => 'database',

        'database' => [

            'driver' => \BeyondCode\LaravelWebSockets\Statistics\Drivers\DatabaseDriver::class,

            'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,

        ],

        /*
        |--------------------------------------------------------------------------
        | Statistics Logger Handler
        |--------------------------------------------------------------------------
        |
        | The Statistics Logger will, by default, handle the incoming statistics,
        | store them into an array and then store them into the database
        | on each interval.
        |
        | You can opt-in to avoid any statistics storage by setting the logger
        | to the built-in NullLogger.
        |
        */

        'logger' => \BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger::class,
        // 'logger' => \BeyondCode\LaravelWebSockets\Statistics\Logger\NullStatisticsLogger::class,

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

        /*
        |--------------------------------------------------------------------------
        | DNS Lookup
        |--------------------------------------------------------------------------
        |
        | Use an DNS resolver to make the requests to the statistics logger
        | default is to resolve everything to 127.0.0.1.
        |
        */

        'perform_dns_lookup' => false,

        /*
        |--------------------------------------------------------------------------
        | DNS Lookup TLS Settings
        |--------------------------------------------------------------------------
        |
        | You can configure the DNS Lookup Connector the TLS settings.
        | Check the available options here:
        | https://github.com/reactphp/socket/blob/master/src/Connector.php#L29
        |
        */

        'tls' => [

            'verify_peer' => env('APP_ENV') === 'production',

            'verify_peer_name' => env('APP_ENV') === 'production',

        ],

    ],

];
