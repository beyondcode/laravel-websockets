<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use stdClass;
use Ratchet\ConnectionInterface;
use React\Promise\PromiseInterface;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;

class PresenceChannel extends Channel
{
    protected $users = [];

    /**
     * @param string $appId
     * @return array|PromiseInterface
     */
    public function getUsers(string $appId)
    {
        if (config('websockets.replication.enabled') === true) {
            // Get the members list from the replication backend
            return app(ReplicationInterface::class)
                ->channelMembers($appId, $this->channelName);
        }

        return $this->users;
    }

    /**
     * @return array
     */
    public function getUserCount()
    {
        return count($this->users);
    }

    /**
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     *
     * @param ConnectionInterface $connection
     * @param stdClass $payload
     * @throws InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        $this->saveConnection($connection);

        $channelData = json_decode($payload->channel_data);
        $this->users[$connection->socketId] = $channelData;

        if (config('websockets.replication.enabled') === true) {
            // Add the connection as a member of the channel
            app(ReplicationInterface::class)
                ->joinChannel(
                    $connection->app->id,
                    $this->channelName,
                    $connection->socketId,
                    json_encode($channelData)
                );

            // We need to pull the channel data from the replication backend,
            // otherwise we won't be sending the full details of the channel
            app(ReplicationInterface::class)
                ->channelMembers($connection->app->id, $this->channelName)
                ->then(function ($users) use ($connection) {
                    // Send the success event
                    $connection->send(json_encode([
                        'event' => 'pusher_internal:subscription_succeeded',
                        'channel' => $this->channelName,
                        'data' => json_encode($this->getChannelData($users)),
                    ]));
                });
        } else {
            // Send the success event
            $connection->send(json_encode([
                'event' => 'pusher_internal:subscription_succeeded',
                'channel' => $this->channelName,
                'data' => json_encode($this->getChannelData($this->users)),
            ]));
        }

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

        if (config('websockets.replication.enabled') === true) {
            // Remove the connection as a member of the channel
            app(ReplicationInterface::class)
                ->leaveChannel(
                    $connection->app->id,
                    $this->channelName,
                    $connection->socketId
                );
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

    /**
     * @return PromiseInterface|array
     */
    public function toArray(string $appId = null)
    {
        if (config('websockets.replication.enabled') === true) {
            return app(ReplicationInterface::class)
                ->channelMembers($appId, $this->channelName)
                ->then(function ($users) {
                    return array_merge(parent::toArray(), [
                        'user_count' => count($users),
                    ]);
                });
        }

        return array_merge(parent::toArray(), [
            'user_count' => count($this->users),
        ]);
    }

    protected function getChannelData(array $users): array
    {
        return [
            'presence' => [
                'ids' => $this->getUserIds($users),
                'hash' => $this->getHash($users),
                'count' => count($users),
            ],
        ];
    }

    protected function getUserIds(array $users): array
    {
        $userIds = array_map(function ($channelData) {
            return (string) $channelData->user_id;
        }, $users);

        return array_values($userIds);
    }

    protected function getHash(array $users): array
    {
        $hash = [];

        foreach ($users as $socketId => $channelData) {
            $hash[$channelData->user_id] = $channelData->user_info;
        }

        return $hash;
    }
}
