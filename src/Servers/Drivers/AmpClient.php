<?php

namespace BeyondCode\LaravelWebSockets\Servers\Drivers;

use Amp\ByteStream\InputStream;
use Amp\LazyPromise;
use Amp\Websocket\Client;
use BeyondCode\LaravelWebSockets\Contracts\Client as ClientContract;
use BeyondCode\LaravelWebSockets\Contracts\Message;
use BeyondCode\LaravelWebSockets\Contracts\Promise;
use BeyondCode\LaravelWebSockets\Loop\Drivers\AmpPromise;
use BeyondCode\LaravelWebSockets\Servers\HandlesClientEvents;
use Generator;
use Illuminate\Support\Collection;

class AmpClient implements ClientContract
{
    use HandlesClientEvents;

    /**
     * Underlying Amp Websocket Client.
     *
     * @var \Amp\Websocket\Client
     */
    protected $client;

    /**
     * On Closing callbacks.
     *
     * @var array|callable<static, int, string>
     */
    protected $events = self::EVENTS;

    /**
     * AmpClient constructor.
     *
     * @param  \Amp\Websocket\Client  $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Returns the underlying client or connection implementation.
     *
     * @return \Amp\Websocket\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Returns the unique connection ID (socket ID) on a server.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->client->getId();
    }

    /**
     * Returns multiple messages using a Generator-style.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Generator<\BeyondCode\LaravelWebSockets\Contracts\Message>>
     */
    public function receive(): Promise
    {
        return new AmpPromise(
            new LazyPromise(
                function (): Generator {
                    while ($message = yield $this->client->receive()) {
                        yield new AmpMessage($message, $this);
                    }
                }
            )
        );
    }

    /**
     * Sends data to the connected client.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $message
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool>  Returns `true` if it was delivered, or `false` if
     *     the connection was closed.
     */
    public function send(Message $message): Promise
    {
        $this->fireOnSendingEvents($message);

        if ($message->isBinary()) {
            return $message->getContents()->then(function (InputStream $contents) {
                $this->client->streamBinary($contents);
                return true;
            });
        }

        $sending = new AmpPromise($this->client->send($message->content()));

        $this->fireOnSendingEvents($message);

        return $sending;
    }

    /**
     * Send a "ping" to the client to check if it's alive.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool>
     */
    public function ping(): Promise
    {
        return new AmpPromise($this->client->ping());
    }

    /**
     * Checks if the last heartbeat was before a given amount of seconds.
     *
     * @param  int  $seconds
     *
     * @return bool
     */
    public function isMissing(int $seconds): bool
    {
        return now()->timestamp - $seconds > $this->client->getInfo()->lastHeartbeatAt;
    }

    /**
     * Returns app-driven metadata associated with this Client.
     *
     * @return \Illuminate\Support\Collection<string>
     */
    public function metadata(): Collection
    {
        return collect();
    }

    /**
     * Closes the connection, sending a last message to the client.
     *
     * @param  int  $code
     * @param  string  $reason
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<null>  It just returns when the connection closed
     */
    public function close(int $code = 1000, string $reason = ''): Promise
    {
        $this->fireOnClosingEvents($code, $reason);

        $promise = new AmpPromise($this->client->close($code, $reason));

        $this->fireOnClosedEvents($code, $reason);

        return $promise;
    }
}
