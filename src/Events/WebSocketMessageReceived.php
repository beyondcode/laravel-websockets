<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ratchet\RFC6455\Messaging\MessageInterface;

class WebSocketMessageReceived
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
     * The message received.
     *
     * @var MessageInterface
     */
    public $message;

    /**
     * The decoded message as array.
     *
     * @var array
     */
    public $decodedMessage;

    /**
     * Create a new event instance.
     *
     * @param  string  $appId
     * @param  string  $socketId
     * @param  MessageInterface  $message
     * @return void
     */
    public function __construct(string $appId, string $socketId, MessageInterface $message)
    {
        $this->appId = $appId;
        $this->socketId = $socketId;
        $this->message = $message;
        $this->decodedMessage = json_decode($message->getPayload(), true);
    }
}
