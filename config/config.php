<?php

return [

    /*
     * TODO: add the laravel style comment here
     */
    'allowedOrigins' => [

    ],

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

    /*
     * TODO:: add client config
     *
     * Default: one item in the array with env PUSHER_APP_ID, _KEY, _SECRET
     *
     * Add notice app id should be numeric
     *
     * "clients": [
        {
            "appId": "cbf9b001405e51d4",
            "key": "d886dd1900a5911d00996b41638d7026"
            "secret":
        }
    ],
`
     *
    'clients' => [
        ...[]
    ],

    'client_provider' => ConfigProvider
    */

];