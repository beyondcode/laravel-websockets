<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use BeyondCode\LaravelWebSockets\WebSocketController;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;

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

        $socketId = sprintf("%d.%d", getmypid(), random_int(1, 100000000));

        // Store the socketId along with the connection so we can retrieve it.
        $conn->socketId = $socketId;

        $conn->send($this->buildPayload('pusher:connection_established', [
            'socket_id' => $socketId,
            'activity_timeout' => 60,
        ]));
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        $payload = json_decode($msg->getPayload());

        dump("Received payload", $payload);

        // todo: validate payload
        $event = camel_case(str_replace(':', '_', $payload->event));

        if (method_exists($this, $event)) {
            call_user_func([$this, $event], $conn, $payload->data);
        }
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
        $channel = $this->channelManager->findOrCreate($payload->channel);
        $channel->subscribe($conn, $payload);
    }

    protected function buildPayload($event, $data = [])
    {
        return json_encode([
            'event' => $event,
            'data' => json_encode($data)
        ]);
    }
}