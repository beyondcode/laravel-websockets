<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use BeyondCode\LaravelWebSockets\WebSocketController;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use stdClass;

class EchoServer extends WebSocketController
{
    /** @var ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {

        dump("Client connected");
        /**
         * There are a couple things we need to do here:
         * 1. Authenticate the incoming request by validating the provided APP-ID is known to us (JSON file lookup?)
         */

        $socketId = sprintf("%d.%d", getmypid(), random_int(1, 100000000));

        // Store the socketId along with the connection so we can retrieve it.
        $conn->socketId = $socketId;

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $conn->httpRequest;

        $queryParameters = [];
        parse_str($request->getUri()->getQuery(), $queryParameters);

        $conn->appId = $queryParameters['appId'];

        $conn->send($this->buildPayload('pusher:connection_established', [
            'socket_id' => $socketId,
            'activity_timeout' => 60,
        ]));
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        $payload = json_decode($msg->getPayload());

        dump("Received payload", $payload);

        /**
         * Pusher events get a special treatment
         */
        if (starts_with($payload->event, 'pusher:')) {
            $event = camel_case(str_replace(':', '_', $payload->event));

            if (method_exists($this, $event)) {
                call_user_func([$this, $event], $conn, $payload->data);
            }
        } else {
            // Try to find a channel and broadcast the message to the clients.
            $channel = $this->channelManager->find($conn->appId, $payload->channel);

            if ($channel) {
                $channel->broadcast($payload);
            }
        }
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->channelManager->removeFromAllChannels($connection);
    }

    /**
     * @link https://pusher.com/docs/pusher_protocol#ping-pong
     * @param ConnectionInterface $conn
     * @param $payload
     */
    protected function pusherPing(ConnectionInterface $conn, $payload)
    {
        $conn->send($this->buildPayload('pusher:pong'));
    }

    /**
     * @link https://pusher.com/docs/pusher_protocol#pusher-subscribe
     * @param ConnectionInterface $conn
     * @param $payload
     */
    protected function pusherSubscribe(ConnectionInterface $conn, $payload)
    {
        $channel = $this->channelManager->findOrCreate($conn->appId, $payload->channel);

        $channel->subscribe($conn, $payload);
    }

    public function pusherUnsubscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->appId, $payload->channel);

        $channel->unsubscribe($connection);

    }

    protected function buildPayload($event, $data = [])
    {
        return json_encode([
            'event' => $event,
            'data' => json_encode($data)
        ]);
    }
}