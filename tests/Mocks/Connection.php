<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;
use PHPUnit\Framework\Assert as PHPUnit;

class Connection implements ConnectionInterface
{
    /** @var Request */
    public $httpRequest;

    public $sentData = [];

    public $closed = false;

    function send($data)
    {
        $this->sentData[] = json_decode($data, true);
    }

    function close()
    {
        $this->closed = true;
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