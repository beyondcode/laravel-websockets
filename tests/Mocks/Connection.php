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

    function send($data)
    {
        $this->sentData[] = json_decode($data, true);
    }

    function close()
    {
        // TODO: Implement close() method.
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
}