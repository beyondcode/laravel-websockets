<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionClosed
{
    use Dispatchable, SerializesModels;

    /**
     * The WebSockets app id that the user connected to.
     *
     * @var string
     */
    public $appId;

    /**
     * The Socket ID associated with the connection.
     *
     * @var string
     */
    public $socketId;

    /**
     * Create a new event instance.
     *
     * @param  string  $appId
     * @param  string  $socketId
     * @return void
     */
    public function __construct(string $appId, string $socketId)
    {
        $this->appId = $appId;
        $this->socketId = $socketId;
    }
}
