<?php

namespace BeyondCode\LaravelWebSockets\Test\Mocks;

use Ratchet\ConnectionInterface;

class SignedMessage extends Message
{
    /**
     * Create a new signed message instance.
     *
     * @param  array  $payload
     * @param  ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  string|null  $encodedUser
     * @return void
     */
    public function __construct(array $payload, ConnectionInterface $connection, string $channelName, string $encodedUser = null)
    {
        parent::__construct($payload);

        $signature = "{$connection->socketId}:{$channelName}";

        if ($encodedUser) {
            $signature .= ":{$encodedUser}";
        }

        $hash = hash_hmac('sha256', $signature, $connection->app->secret);

        $this->payload['data']['auth'] = "{$connection->app->key}:{$hash}";
    }
}
