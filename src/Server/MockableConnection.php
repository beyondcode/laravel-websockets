<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Ratchet\ConnectionInterface;
use stdClass;

class MockableConnection implements ConnectionInterface
{
    /**
     * Create a new Mockable connection.
     *
     * @param  string|int  $appId
     * @param  string  $socketId
     * @return void
     */
    public function __construct($appId, string $socketId)
    {
        $this->app = new stdClass;

        $this->app->id = $appId;
        $this->socketId = $socketId;
    }

    /**
     * Send data to the connection.
     *
     * @param  string  $data
     * @return \Ratchet\ConnectionInterface
     */
    public function send($data)
    {
        //
    }

    /**
     * Close the connection.
     *
     * @return void
     */
    public function close()
    {
        //
    }
}
