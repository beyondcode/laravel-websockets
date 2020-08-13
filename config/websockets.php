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

        'app' => \BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider::class,

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
     * This package comes with multi tenancy out of the box. Here you can
     * configure the different apps that can use the webSockets server.
     *
     * Optionally you specify capacity so you can limit the maximum
     * concurrent connections for a specific app.
     *
     * Optionally you can disable client events so clients cannot send
     * messages to each other via the webSockets.
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
        ],
    ],

    /*
     * This array contains the hosts of which you want to allow incoming requests.
     * Leave this empty if you want to accept requests from all hosts.
     */
    'allowed_origins' => [
        //
    ],

    /*
     * The maximum request size in kilobytes that is allowed for an incoming WebSocket request.
     */
    'max_request_size_in_kb' => 250,

    /*
     * Define the optional SSL context for your WebSocket connections.
     * You can see all available options at: http://php.net/manual/en/context.ssl.php
     */
    'ssl' => [
        /*
         * Path to local certificate file on filesystem. It must be a PEM encoded file which
         * contains your certificate and private key. It can optionally contain the
         * certificate chain of issuers. The private key also may be contained
         * in a separate file specified by local_pk.
         */
        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),

        /*
         * Path to local private key file on filesystem in case of separate files for
         * certificate (local_cert) and private key.
         */
        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),

        /*
         * Passphrase for your local_cert file.
         */
        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE', null),
    ],

    'statistics' => [

        /*
         * This model will be used to store the statistics of the WebSocketsServer.
         * The only requirement is that the model should extend
         * `WebSocketsStatisticsEntry` provided by this package.
         */
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,

        /**
         * The Statistics Logger will, by default, handle the incoming statistics, store them
         * and then release them into the database on each interval defined below.
         */

        'logger' => \BeyondCode\LaravelWebSockets\Statistics\Logger::class,

        /*
         * Here you can specify the interval in seconds at which statistics should be logged.
         */

        'interval_in_seconds' => 60,

        /*
         * When the clean-command is executed, all recorded statistics older than
         * the number of days specified here will be deleted.
         */

        'delete_statistics_older_than_days' => 60,

        /*
         * Use an DNS resolver to make the requests to the statistics logger
         * default is to resolve everything to 127.0.0.1.
         */

        'perform_dns_lookup' => false,
    ],

];
