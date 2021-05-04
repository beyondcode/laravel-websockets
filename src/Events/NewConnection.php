<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConnection
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
    public $clientId;

    /**
     * Create a new event instance.
     *
     * @param  string  $appId
     * @param  string  $clientId
     * @return void
     */
    public function __construct(string $appId, string $clientId)
    {
        $this->appId = $appId;
        $this->clientId = $clientId;
    }
}
