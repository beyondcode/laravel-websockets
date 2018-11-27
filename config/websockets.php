<?php

use BeyondCode\LaravelWebSockets\ClientProviders\ConfigClientProvider;

return [

    /**
     * This package comes with multi tenancy out of the box. Here you can
     * configure the different clients that can use the webSockets server.
     *
     * You should make sure that the app id is numeric.
     */
    'clients' => [
        [
            'name' => env('APP_NAME'),
            'app_id' => env('PUSHER_APP_ID'),
            'app_key' => env('PUSHER_APP_KEY'),
            'app_secret' => env('PUSHER_APP_SECRET')
        ],
    ],

    /**
     * This class is responsible for finding the clients. The default provider
     * will use the clients defined in this config file.
     *
     * You can create a custom provider by implementing the
     * `ClientProvier` interface.
     */
    'client_provider' => ConfigClientProvider::class,

    /*
     * This array contains the hosts of which you want to allow incoming requests.
     * Leave this empty if you want to accepts requests from all hosts.
     */
    'allowedOrigins' => [
        //
    ],

    /*
     * The maximum request size in bytes that is allowed for an incoming websocket request.
     */
    'maxRequestSize' => 1024 * 250,

    /*
     * Define the optional SSL context for your websocket connections.
     * You can see all available options at: http://php.net/manual/en/context.ssl.php
     */
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
];