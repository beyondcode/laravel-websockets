<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use stdClass;

class UnsubscribedFromChannel
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
     * The channel name.
     *
     * @var string
     */
    public $channelName;

    /**
     * The user received on presence channel.
     *
     * @var string
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  string  $appId
     * @param  string  $clientId
     * @param  string  $channelName
     * @param  object|null  $user
     */
    public function __construct(string $appId, string $clientId, string $channelName, ?object $user = null)
    {
        $this->appId = $appId;
        $this->clientId = $clientId;
        $this->channelName = $channelName;
        $this->user = $user;
    }
}
