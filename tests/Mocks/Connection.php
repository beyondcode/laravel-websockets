<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert as PHPUnit;
use Ratchet\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var Request */
    public $httpRequest;

    public $sentData = [];

    public $sentRawData = [];

    public $closed = false;

    public function send($data)
    {
        $this->sentData[] = json_decode($data, true);
        $this->sentRawData[] = $data;
    }

    public function close()
    {
        $this->closed = true;
    }

    public function resetEvents()
    {
        $this->sentData = [];
        $this->sentRawData = [];
    }

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

    public function assertNotSentEvent(string $name)
    {
        $event = collect($this->sentData)->firstWhere('event', '=', $name);

        PHPUnit::assertTrue(
            is_null($event)
        );
    }

    public function assertClosed()
    {
        PHPUnit::assertTrue($this->closed);
    }
}
