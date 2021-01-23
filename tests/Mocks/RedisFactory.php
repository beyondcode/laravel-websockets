<?php

namespace BeyondCode\LaravelWebSockets\Test\Mocks;

use Clue\React\Redis\Factory;
use Clue\Redis\Protocol\Factory as ProtocolFactory;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectorInterface;

class RedisFactory extends Factory
{
    /**
     * The loop instance.
     *
     * @var LoopInterface
     */
    private $loop;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null, ProtocolFactory $protocol = null)
    {
        parent::__construct($loop, $connector, $protocol);

        $this->loop = $loop;
    }

    /**
     * Create Redis client connected to address of given redis instance.
     *
     * @param  string  $target
     * @return Client
     */
    public function createLazyClient($target)
    {
        return new LazyClient($target, $this, $this->loop);
    }
}
