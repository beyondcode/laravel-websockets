<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Pusher\Channels;

use Ratchet\ConnectionInterface;
use stdClass;

class PresenceChannel extends Channel
{
    protected $users = [];

    public function getUsers(): array
    {
        return $this->users;
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        $this->saveConnection($connection);

        $channelData = json_decode($payload->channel_data);
        $this->users[$channelData->user_id] = $channelData;

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

        $this->broadcastToOthers($connection, [
            'event' => 'pusher_internal:member_removed',
            'channel' => $this->channelId,
            'data' => json_encode([
                'user_id' => $this->users[$connection->socketId]->user_id
            ])
        ]);

        unset($this->users[$connection->socketId]);
    }

    protected function getChannelData(): array
    {
        $hash = [];
        foreach ($this->users as $socketId => $channelData) {
            $hash[$channelData->user_id] = $channelData->user_info;
        }

        return [
            'presence' => [
                'ids' => array_values(array_map(function($channelData) { return (string)$channelData->user_id; }, $this->users)),
                'hash' => $hash,
                'count' => count($this->users)
            ]
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(),[
            'user_count' => count($this->users),
        ]);
    }
}