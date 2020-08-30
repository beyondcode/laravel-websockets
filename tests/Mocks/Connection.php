<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert as PHPUnit;
use Ratchet\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /**
     * The request instance.
     *
     * @var Request
     */
    public $httpRequest;

    /**
     * The sent data through the connection.
     *
     * @var array
     */
    public $sentData = [];

    /**
     * The raw (unencoded) sent data.
     *
     * @var array
     */
    public $sentRawData = [];

    /**
     * Wether the connection has been closed.
     *
     * @var bool
     */
    public $closed = false;

    /**
     * Send the data through the connection.
     *
     * @param  mixed  $data
     * @return void
     */
    public function send($data)
    {
        $this->sentData[] = json_decode($data, true);
        $this->sentRawData[] = $data;
    }

    /**
     * Mark the connection as closed.
     *
     * @return void
     */
    public function close()
    {
        $this->closed = true;
    }

    /**
     * Assert that an event got sent.
     *
     * @param  string  $name
     * @param  array  $additionalParameters
     * @return void
     */
    public function assertSentEvent(string $name, array $additionalParameters = [])
    {
        $event = collect($this->sentData)->firstWhere('event', '=', $name);

        PHPUnit::assertTrue(
            ! is_null($event)
        );

        foreach ($additionalParameters as $parameter => $value) {
            PHPUnit::assertSame($event[$parameter], $value);
        }
    }

    /**
     * Assert that an event got not sent.
     *
     * @param  string  $name
     * @return void
     */
    public function assertNotSentEvent(string $name)
    {
        $event = collect($this->sentData)->firstWhere('event', '=', $name);

        PHPUnit::assertTrue(
            is_null($event)
        );
    }

    /**
     * Assert the connection is closed.
     *
     * @return void
     */
    public function assertClosed()
    {
        PHPUnit::assertTrue($this->closed);
    }
}
