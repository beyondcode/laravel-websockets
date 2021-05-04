<?php

namespace BeyondCode\LaravelWebSockets\Events;

use BeyondCode\LaravelWebSockets\Contracts\Message;
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
    public $clientId;

    /**
     * The message received.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\Message
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
     * @param  string  $clientId
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $message
     * @return void
     */
    public function __construct(string $appId, string $clientId, Message $message)
    {
        $this->appId = $appId;
        $this->clientId = $clientId;
        $this->message = $message;
        $this->decodedMessage = json_decode($message->getPayload(), true);
    }
}
