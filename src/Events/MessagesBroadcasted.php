<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ratchet\ConnectionInterface;

class MessagesBroadcasted
{
    use Dispatchable, SerializesModels;

    /**
     * The amount of messages sent.
     *
     * @var int
     */
    protected $sentMessagesCount;

    /**
     * Initialize the event.
     *
     * @param  int  $sentMessagesCount
     * @return void
     */
    public function __construct(int $sentMessagesCount = 0)
    {
        $this->sentMessagesCount = $sentMessagesCount;
    }
}
