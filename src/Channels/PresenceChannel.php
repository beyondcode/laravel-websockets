<?php

namespace BeyondCode\LaravelWebSockets\Channels;

use BeyondCode\LaravelWebSockets\DashboardLogger;
use BeyondCode\LaravelWebSockets\Events\SubscribedToChannel;
use BeyondCode\LaravelWebSockets\Events\UnsubscribedFromChannel;
use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;
use stdClass;

class PresenceChannel extends PrivateChannel
{
    /**
     * Subscribe to the channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#presence-channel-events
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return bool
     *
     * @throws InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload): bool
    {
        $this->verifySignature($connection, $payload);

        $this->saveConnection($connection);

        $user = json_decode($payload->channel_data);

        $this->channelManager
            ->userJoinedPresenceChannel($connection, $user, $this->getName(), $payload)
            ->then(function () use ($connection) {
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
            })
            ->then(function () use ($connection, $user, $payload) {
                // The `pusher_internal:member_added` event is triggered when a user joins a channel.
                // It's quite possible that a user can have multiple connections to the same channel
                // (for example by having multiple browser tabs open)
                // and in this case the events will only be triggered when the first tab is opened.
                $this->channelManager
                    ->getMemberSockets($user->user_id, $connection->app->id, $this->getName())
                    ->then(function ($sockets) use ($payload, $connection, $user) {
                        if (count($sockets) === 1) {
                            $memberAddedPayload = [
                                'event' => 'pusher_internal:member_added',
                                'channel' => $this->getName(),
                                'data' => $payload->channel_data,
                            ];

                            $this->broadcastToEveryoneExcept(
                                (object) $memberAddedPayload, $connection->socketId,
                                $connection->app->id
                            );

                            SubscribedToChannel::dispatch(
                                $connection->app->id,
                                $connection->socketId,
                                $this->getName(),
                                $user
                            );
                        }

                        DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_SUBSCRIBED, [
                            'socketId' => $connection->socketId,
                            'channel' => $this->getName(),
                            'duplicate-connection' => count($sockets) > 1,
                        ]);
                    });
            });

        return true;
    }

    /**
     * Unsubscribe connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return bool
     */
    public function unsubscribe(ConnectionInterface $connection): bool
    {
        $truth = parent::unsubscribe($connection);

        $this->channelManager
            ->getChannelMember($connection, $this->getName())
            ->then(function ($user) {
                return @json_decode($user);
            })
            ->then(function ($user) use ($connection) {
                if (! $user) {
                    return;
                }

                $this->channelManager
                    ->userLeftPresenceChannel($connection, $user, $this->getName())
                    ->then(function () use ($connection, $user) {
                        // The `pusher_internal:member_removed` is triggered when a user leaves a channel.
                        // It's quite possible that a user can have multiple connections to the same channel
                        // (for example by having multiple browser tabs open)
                        // and in this case the events will only be triggered when the last one is closed.
                        $this->channelManager
                            ->getMemberSockets($user->user_id, $connection->app->id, $this->getName())
                            ->then(function ($sockets) use ($connection, $user) {
                                if (count($sockets) === 0) {
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

                                    UnsubscribedFromChannel::dispatch(
                                        $connection->app->id,
                                        $connection->socketId,
                                        $this->getName(),
                                        $user
                                    );
                                }
                            });
                    });
            });

        return $truth;
    }
}
