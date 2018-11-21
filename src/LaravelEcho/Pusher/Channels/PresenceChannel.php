<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;

use Ratchet\ConnectionInterface;

class PresenceChannel extends Channel
{
    protected $subscriptions = [];

    public function subscribe(ConnectionInterface $conn, $payload)
    {
        $channelData = json_decode($payload->channel_data);
        $this->subscriptions[$channelData->user_id] = $channelData;

        // Send the success event
        $conn->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelId,
            'data' => json_encode($this->getChannelData())
        ]));
    }

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