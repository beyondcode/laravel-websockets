<?php

namespace BeyondCode\LaravelWebSockets\Channels;

use BeyondCode\LaravelWebSockets\DashboardLogger;
use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;
use stdClass;

class PresenceChannel extends PrivateChannel
{
    /**
     * Subscribe to the channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#presence-channel-events
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     * @throws InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        $this->saveConnection($connection);

        $this->channelManager->userJoinedPresenceChannel(
            $connection,
            $user = json_decode($payload->channel_data),
            $this->getName(),
            $payload
        );

        $this->channelManager
            ->getChannelMembers($connection->app->id, $this->getName())
            ->then(function ($users) use ($connection) {
                $hash = [];

                foreach ($users as $socketId => $user) {
                    $hash[$user->user_id] = $user->user_info ?? [];
                }

                $connection->send(json_encode([
                    'event' => 'pusher_internal:subscription_succeeded',
                    'channel' => $this->getName(),
                    'data' => json_encode([
                        'presence' => [
                            'ids' => collect($users)->map(function ($user) {
                                return (string) $user->user_id;
                            })->values(),
                            'hash' => $hash,
                            'count' => count($users),
                        ],
                    ]),
                ]));
            });

        $memberAddedPayload = [
            'event' => 'pusher_internal:member_added',
            'channel' => $this->getName(),
            'data' => $payload->channel_data,
        ];

        $this->broadcastToEveryoneExcept(
            (object) $memberAddedPayload, $connection->socketId,
            $connection->app->id
        );

        DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_SUBSCRIBED, [
            'socketId' => $connection->socketId,
            'channel' => $this->getName(),
        ]);
    }

    /**
     * Unsubscribe connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function unsubscribe(ConnectionInterface $connection)
    {
        parent::unsubscribe($connection);

        $this->channelManager
            ->getChannelMember($connection, $this->getName())
            ->then(function ($user) use ($connection) {
                $user = @json_decode($user);

                if (! $user) {
                    return;
                }

                $this->channelManager->userLeftPresenceChannel(
                    $connection, $user, $this->getName()
                );

                $memberRemovedPayload = [
                    'event' => 'pusher_internal:member_removed',
                    'channel' => $this->getName(),
                    'data' => json_encode([
                        'user_id' => $user->user_id,
                    ]),
                ];

                $this->broadcastToEveryoneExcept(
                    (object) $memberRemovedPayload, $connection->socketId,
                    $connection->app->id
                );
            });
    }
}
