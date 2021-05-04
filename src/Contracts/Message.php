<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

interface Message
{
    /**
     * Checks if the message is plain text, hopefully JSON.
     *
     * @return bool
     */
    public function isText(): bool;

    /**
     * Checks if the message is binary data.
     *
     * @return bool
     */
    public function isBinary(): bool;

    /**
     * Reads the whole message contents.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<string> A promise with the full contents.
     */
    public function getContents(): Promise;

    /**
     * Reads the whole message.
     *
     * @return string
     */
    public function content(): string;

    /**
     * Returns the contents as JSON.
     *
     * @param  string|null  $key  If a key is issued, the value of the key will be returned.
     * @param  mixed  $default
     *
     * @return \BeyondCode\LaravelWebSockets\JsonMessage|mixed|null
     */
    public function json(string $key = null, $default = null);

    /**
     * Reads the streamed message as it is received by chunks.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Iterator>  A promise yielding each part.
     */
    public function streamContents(): Promise;
}
