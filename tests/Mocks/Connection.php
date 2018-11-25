<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;
use PHPUnit\Framework\Assert as PHPUnit;

class Connection implements ConnectionInterface
{
    /** @var Request */
    public $httpRequest;

    protected $sentData = [];

    function send($data)
    {
        $this->sentData[] = json_decode($data, true);
    }

    function close()
    {
        // TODO: Implement close() method.
    }

    public function assertSentEvent(string $name)
    {
        PHPUnit::assertTrue(
            ! is_null(collect($this->sentData)->firstWhere('event', '=', $name))
        );
    }
}