<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ratchet\ConnectionInterface;

class Subscribed
{
    use Dispatchable, SerializesModels;

    /**
     * The channel name the user has subscribed to.
     *
     * @var string
     */
    protected $channelName;

    /**
     * The connection that initiated the subscription.
     *
     * @var \Ratchet\ConnectionInterface
     */
    protected $connection;

    /**
     * Initialize the event.
     *
     * @param  string  $channelName
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function __construct(string $channelName, ConnectionInterface $connection)
    {
        $this->channelName = $channelName;
        $this->connection = $connection;
    }
}
