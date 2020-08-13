<?php

namespace BeyondCode\LaravelWebSockets\PubSub\Drivers;

use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use stdClass;

class LocalClient implements ReplicationInterface
{
    /**
     * Mapping of the presence JSON data for users in each channel.
     *
     * @var string[][]
     */
    protected $channelData = [];

    /**
     * Boot the pub/sub provider (open connections, initial subscriptions, etc).
     *
     * @param LoopInterface $loop
     * @return self
     */
    public function boot(LoopInterface $loop): ReplicationInterface
    {
        return $this;
    }

    /**
     * Publish a payload on a specific channel, for a specific app.
     *
     * @param string $appId
     * @param string $channel
     * @param stdClass $payload
     * @return bool
     */
    public function publish(string $appId, string $channel, stdClass $payload): bool
    {
        // Nothing to do, nobody to publish to
        return true;
    }

    /**
     * Subscribe to receive messages for a channel.
     *
     * @param string $appId
     * @param string $channel
     * @return bool
     */
    public function subscribe(string $appId, string $channel): bool
    {
        return true;
    }

    /**
     * Unsubscribe from a channel.
     *
     * @param string $appId
     * @param string $channel
     * @return bool
     */
    public function unsubscribe(string $appId, string $channel): bool
    {
        return true;
    }

    /**
     * Add a member to a channel. To be called when they have
     * subscribed to the channel.
     *
     * @param string $appId
     * @param string $channel
     * @param string $socketId
     * @param string $data
     */
    public function joinChannel(string $appId, string $channel, string $socketId, string $data)
    {
        $this->channelData["$appId:$channel"][$socketId] = $data;
    }

    /**
     * Remove a member from the channel. To be called when they have
     * unsubscribed from the channel.
     *
     * @param string $appId
     * @param string $channel
     * @param string $socketId
     */
    public function leaveChannel(string $appId, string $channel, string $socketId)
    {
        unset($this->channelData["$appId:$channel"][$socketId]);
        if (empty($this->channelData["$appId:$channel"])) {
            unset($this->channelData["$appId:$channel"]);
        }
    }

    /**
     * Retrieve the full information about the members in a presence channel.
     *
     * @param string $appId
     * @param string $channel
     * @return PromiseInterface
     */
    public function channelMembers(string $appId, string $channel): PromiseInterface
    {
        $members = $this->channelData["$appId:$channel"] ?? [];

        // The data is expected as objects, so we need to JSON decode
        $members = array_map(function ($user) {
            return json_decode($user);
        }, $members);

        return new FulfilledPromise($members);
    }

    /**
     * Get the amount of users subscribed for each presence channel.
     *
     * @param string $appId
     * @param array $channelNames
     * @return PromiseInterface
     */
    public function channelMemberCounts(string $appId, array $channelNames): PromiseInterface
    {
        $results = [];

        // Count the number of users per channel
        foreach ($channelNames as $channel) {
            $results[$channel] = isset($this->channelData["$appId:$channel"])
                ? count($this->channelData["$appId:$channel"])
                : 0;
        }

        return new FulfilledPromise($results);
    }
}
