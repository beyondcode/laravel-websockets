<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;

use Ratchet\ConnectionInterface;

class PresenceChannel extends Channel
{
    protected $subscriptions = [];

    /*
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     */
    public function subscribe(ConnectionInterface $connection, $payload)
    {
        $this->saveConnection($connection);

        $channelData = json_decode($payload->channel_data);
        $this->subscriptions[$channelData->user_id] = $channelData;

        // Send the success event
        $connection->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelId,
            'data' => json_encode($this->getChannelData())
        ]));

        $this->broadcastToOthers($connection, [
            'event' => 'pusher_internal:member_added',
            'channel' => $this->channelId,
            'data' => json_encode($channelData)
        ]);
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        parent::unsubscribe($connection);

        //TODO: send member_removed message back to client, and broadcast to everyone on channel
    }

    protected function getChannelData(): array
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