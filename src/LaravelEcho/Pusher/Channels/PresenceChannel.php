<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;

use Ratchet\ConnectionInterface;

class PresenceChannel extends Channel
{
    protected $subscriptions = [];

    /**
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     *
     * @param ConnectionInterface $conn
     * @param $payload
     */
    public function subscribe(ConnectionInterface $conn, $payload)
    {
        $this->saveConnection($conn);

        $channelData = json_decode($payload->channel_data);
        $this->subscriptions[$channelData->user_id] = $channelData;

        // Send the success event
        $conn->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelId,
            'data' => json_encode($this->getChannelData())
        ]));

        //TODO: send member_added message back to client, and broadcast to everyone on channel
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        parent::unsubscribe($connection);

        //TODO: send member_removed message back to client, and broadcast to everyone on channel
    }

    /**
     * @return array
     */
    protected function getChannelData()
    {
        return [
            'presence' => [
                'ids' => array_keys($this->subscriptions),
                'hash' => array_map(function($channelData) { return $channelData->user_info; }, $this->subscriptions),
                'count' => count($this->subscriptions)
            ]
        ];
    }
}