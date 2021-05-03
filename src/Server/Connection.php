<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Amp\Websocket\Client;
use BeyondCode\LaravelWebSockets\Contracts\Connection as WebsocketConnectionContract;
use Illuminate\Support\Traits\ForwardsCalls;

class Connection implements WebsocketConnectionContract
{
    use ForwardsCalls;

    /**
     * The underlying client
     *
     * @var \Amp\Websocket\Client
     */
    protected $client;

    /**
     * The application ID connected to
     *
     * @var int|string|null
     */
    protected $appId;

    /**
     * WebsocketConnection constructor.
     *
     * @param  \Amp\Websocket\Client  $client
     * @param  int|string $appId
     */
    public function __construct(Client $client, $appId = null)
    {
        $this->client = $client;
        $this->appId = $appId;
    }

    /**
     * The underlying client.
     *
     * @return \Amp\Websocket\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Returns the connection ID of the client.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->client->getId();
    }

    /**
     * The Application ID its connected to.
     *
     * @return int|string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param  string  $name
     * @param  array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $this->forwardCallTo($this->client, $name, $arguments);
    }
}
