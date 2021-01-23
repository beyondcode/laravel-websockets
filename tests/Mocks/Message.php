<?php

namespace BeyondCode\LaravelWebSockets\Test\Mocks;

use Ratchet\RFC6455\Messaging\Message as BaseMessage;

class Message extends BaseMessage
{
    /**
     * The payload as array.
     *
     * @var array
     */
    protected $payload;

    /**
     * Create a new message instance.
     *
     * @param  array  $payload
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get the payload as json-encoded string.
     *
     * @return string
     */
    public function getPayload(): string
    {
        return json_encode($this->payload);
    }

    /**
     * Get the payload as object.
     *
     * @return stdClass
     */
    public function getPayloadAsObject()
    {
        return json_decode($this->getPayload());
    }

    /**
     * Get the payload as array.
     *
     * @return stdClass
     */
    public function getPayloadAsArray(): array
    {
        return $this->payload;
    }
}
