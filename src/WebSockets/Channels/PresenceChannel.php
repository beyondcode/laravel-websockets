<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;
use React\Promise\PromiseInterface;
use stdClass;

class PresenceChannel extends Channel
{
    /**
     * Data for the users connected to this channel.
     *
     * Note: If replication is enabled, this will only contain entries
     * for the users directly connected to this server instance. Requests
     * for data for all users in the channel should be routed through
     * ReplicationInterface.
     *
     * @var string[]
     */
    protected $users = [];

    /**
     * @param string $appId
     * @return PromiseInterface
     */
    public function getUsers(string $appId)
    {
        // Get the members list from the replication backend
        return $this->replicator
            ->channelMembers($appId, $this->channelName);
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

        // Add the connection as a member of the channel
        $this->replicator
            ->joinChannel(
                $connection->app->id,
                $this->channelName,
                $connection->socketId,
                json_encode($channelData)
            );

        // We need to pull the channel data from the replication backend,
        // otherwise we won't be sending the full details of the channel
        $this->replicator
            ->channelMembers($connection->app->id, $this->channelName)
            ->then(function ($users) use ($connection) {
                // Send the success event
                $connection->send(json_encode([
                    'event' => 'pusher_internal:subscription_succeeded',
                    'channel' => $this->channelName,
                    'data' => json_encode($this->getChannelData($users)),
                ]));
            });

        $this->broadcastToOthers($connection, (object) [
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

        // Remove the connection as a member of the channel
        $this->replicator
            ->leaveChannel(
                $connection->app->id,
                $this->channelName,
                $connection->socketId
            );

        $this->broadcastToOthers($connection, (object) [
            'event' => 'pusher_internal:member_removed',
            'channel' => $this->channelName,
            'data' => json_encode([
                'user_id' => $this->users[$connection->socketId]->user_id,
            ]),
        ]);

        unset($this->users[$connection->socketId]);
    }

    /**
     * @param string|null $appId
     * @return PromiseInterface
     */
    public function toArray(string $appId = null)
    {
        return $this->replicator
            ->channelMembers($appId, $this->channelName)
            ->then(function ($users) {
                return array_merge(parent::toArray(), [
                    'user_count' => count($users),
                ]);
            });
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

    /**
     * Compute the hash for the presence channel integrity.
     *
     * @param  array  $users
     * @return array
     */
    protected function getHash(array $users): array
    {
        $hash = [];

        foreach ($users as $socketId => $channelData) {
            $hash[$channelData->user_id] = $channelData->user_info ?? [];
        }

        return $hash;
    }
}
