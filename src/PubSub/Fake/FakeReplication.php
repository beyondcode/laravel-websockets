<?php

namespace BeyondCode\LaravelWebSockets\PubSub\Fake;

use stdClass;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;

class FakeReplication implements ReplicationInterface
{
    protected $channels = [];

    /**
     * Boot the pub/sub provider (open connections, initial subscriptions, etc).
     *
     * @param LoopInterface $loop
     * @return self
     */
    public function boot(LoopInterface $loop) : ReplicationInterface
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
    public function publish(string $appId, string $channel, stdClass $payload) : bool
    {
        return true;
    }

    /**
     * Subscribe to receive messages for a channel.
     *
     * @param string $appId
     * @param string $channel
     * @return bool
     */
    public function subscribe(string $appId, string $channel) : bool
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
    public function unsubscribe(string $appId, string $channel) : bool
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
        $this->channels["$appId:$channel"][$socketId] = $data;
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
        unset($this->channels["$appId:$channel"][$socketId]);
        if (empty($this->channels["$appId:$channel"])) {
            unset($this->channels["$appId:$channel"]);
        }
    }

    /**
     * Retrieve the full information about the members in a presence channel.
     *
     * @param string $appId
     * @param string $channel
     * @return PromiseInterface
     */
    public function channelMembers(string $appId, string $channel) : PromiseInterface
    {
        $data = array_map(function ($user) {
            return json_decode($user);
        }, $this->channels["$appId:$channel"]);

        return new FulfilledPromise($data);
    }

    /**
     * Get the amount of users subscribed for each presence channel.
     *
     * @param string $appId
     * @param array $channelNames
     * @return PromiseInterface
     */
    public function channelMemberCounts(string $appId, array $channelNames) : PromiseInterface
    {
        $data = [];

        foreach ($channelNames as $channel) {
            $data[$channel] = count($this->channels["$appId:$channel"]);
        }

        return new FulfilledPromise($data);
    }
}
