<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use stdClass;

class SubscribedToChannel
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
     * @param  string  $socketId
     * @param  string  $channelName
     * @param  stdClass|null  $user
     * @return void
     */
    public function __construct(string $appId, string $socketId, string $channelName, ?stdClass $user = null)
    {
        $this->appId = $appId;
        $this->socketId = $socketId;
        $this->channelName = $channelName;
        $this->user = $user;
    }
}
