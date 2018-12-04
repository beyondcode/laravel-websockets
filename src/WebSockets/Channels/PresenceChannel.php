<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use stdClass;
use Ratchet\ConnectionInterface;

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
        $this->users[$connection->socketId] = $channelData;

        // Send the success event
        $connection->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelName,
            'data' => json_encode($this->getChannelData()),
        ]));

        $this->broadcastToOthers($connection, [
            'event' => 'pusher_internal:member_added',
            'channel' => $this->channelName,
            'data' => json_encode($channelData),
        ]);
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        parent::unsubscribe($connection);

        if (! isset($this->users[$connection->socketId])) {
            return;
        }

        $this->broadcastToOthers($connection, [
            'event' => 'pusher_internal:member_removed',
            'channel' => $this->channelName,
            'data' => json_encode([
                'user_id' => $this->users[$connection->socketId]->user_id,
            ]),
        ]);

        unset($this->users[$connection->socketId]);
    }

    protected function getChannelData(): array
    {
        return [
            'presence' => [
                'ids' => $this->getUserIds(),
                'hash' => $this->getHash(),
                'count' => count($this->users),
            ],
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'user_count' => count($this->users),
        ]);
    }

    protected function getUserIds(): array
    {
        $userIds = array_map(function ($channelData) {
            return (string) $channelData->user_id;
        }, $this->users);

        return array_values($userIds);
    }

    protected function getHash(): array
    {
        $hash = [];

        foreach ($this->users as $socketId => $channelData) {
            $hash[$channelData->user_id] = $channelData->user_info;
        }

        return $hash;
    }
}
