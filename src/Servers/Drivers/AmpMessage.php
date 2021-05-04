<?php

namespace BeyondCode\LaravelWebSockets\Servers\Drivers;

use Amp\ByteStream\InMemoryStream;
use Amp\ByteStream\InputStream;
use Amp\Websocket\Message;
use BeyondCode\LaravelWebSockets\Contracts\Client;
use BeyondCode\LaravelWebSockets\Contracts\ClientMessage as MessageContract;
use BeyondCode\LaravelWebSockets\Contracts\Promise;
use BeyondCode\LaravelWebSockets\JsonMessage;
use BeyondCode\LaravelWebSockets\Loop\Drivers\AmpPromise;

class AmpMessage implements MessageContract
{
    /**
     * Underling Amp Message.
     *
     * @var \Amp\Websocket\Message
     */
    public $message;

    /**
     * Cached JSON message.
     *
     * @var \BeyondCode\LaravelWebSockets\JsonMessage|null
     */
    protected $json = null;

    /**
     * Amp Client.
     *
     * @var \BeyondCode\LaravelWebSockets\Servers\Drivers\AmpClient
     */
    protected $client;

    /**
     * AmpMessage constructor.
     *
     * @param  \Amp\Websocket\Message  $message
     * @param  \BeyondCode\LaravelWebSockets\Servers\Drivers\AmpClient  $client
     */
    public function __construct(Message $message, AmpClient $client)
    {
        $this->message = $message;
        $this->client = $client;
    }

    /**
     * Checks if the message is plain text, hopefully JSON.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return $this->message->isText();
    }

    /**
     * Checks if the message is binary data.
     *
     * @return bool
     */
    public function isBinary(): bool
    {
        return $this->message->isBinary();
    }

    /**
     * Reads the whole message.
     *
     * @return string
     */
    public function content(): string
    {
        return $this->getContents()->return();
    }

    /**
     * Returns the contents as JSON.
     *
     * @param  string|null  $key  If a key is issued, the value of the key will be returned.
     * @param  mixed  $default
     *
     * @return \BeyondCode\LaravelWebSockets\JsonMessage|mixed|null
     */
    public function json(string $key = null, $default = null)
    {
        $this->json ?? $this->json = new JsonMessage(json_decode($this->content(), true) ?: []);

        if ($key) {
            return $this->json->get($key, $default);
        }

        return $this->json;
    }

    /**
     * Reads the whole message contents.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<string> A promise with the full contents.
     */
    public function getContents(): Promise
    {
        return new AmpPromise($this->message->buffer());
    }

    /**
     * Reads the streamed message as it is received by chunks.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Iterator>  A promise yielding each part.
     */
    public function streamContents(): Promise
    {
        return new AmpPromise($this->message->read());
    }

    /**
     * Returns the Client that sent the message.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Creates a Message from a string message.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     * @param $message
     *
     * @return static
     */
    public static function fromText(Client $client, $message): self
    {
        if (! is_string($message)) {
            $message = json_encode($message);
        }

        return new static(Message::fromText(new InMemoryStream($message)), $client);
    }
}
