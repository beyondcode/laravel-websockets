<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

class ArrayChannelManager implements ChannelManager
{
    /**
     * The app id.
     *
     * @var mixed
     */
    protected $appId;

    /**
     * The list of channels.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Find a channel by name or create one.
     *
     * @param  mixed  $appId
     * @param  string  $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels
     */
    public function findOrCreate($appId, string $channelName): Channel
    {
        if (! isset($this->channels[$appId][$channelName])) {
            $channelClass = $this->determineChannelClass($channelName);

            $this->channels[$appId][$channelName] = new $channelClass($channelName);
        }

        return $this->channels[$appId][$channelName];
    }

    /**
     * Find a channel by name.
     *
     * @param  mixed  $appId
     * @param  string  $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels
     */
    public function find($appId, string $channelName): ?Channel
    {
        return $this->channels[$appId][$channelName] ?? null;
    }

    /**
     * Get all channels.
     *
     * @param  mixed  $appId
     * @return array
     */
    public function getChannels($appId): array
    {
        return $this->channels[$appId] ?? [];
    }

    /**
     * Get the connections count on the app.
     *
     * @param  mixed  $appId
     * @return int|\React\Promise\PromiseInterface
     */
    public function getLocalConnectionsCount($appId): int
    {
        return collect($this->getChannels($appId))
            ->flatMap(function (Channel $channel) {
                return collect($channel->getSubscribedConnections())->pluck('socketId');
            })
            ->unique()
            ->count();
    }

    /**
     * Get the connections count across multiple servers.
     *
     * @param  mixed  $appId
     * @return int|\React\Promise\PromiseInterface
     */
    public function getGlobalConnectionsCount($appId)
    {
        return $this->getLocalConnectionsCount($appId);
    }

    /**
     * Remove connection from all channels.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function removeFromAllChannels(ConnectionInterface $connection)
    {
        if (! isset($connection->app)) {
            return;
        }

        collect(Arr::get($this->channels, $connection->app->id, []))
            ->each->unsubscribe($connection);

        collect(Arr::get($this->channels, $connection->app->id, []))
            ->reject->hasConnections()
            ->each(function (Channel $channel, string $channelName) use ($connection) {
                unset($this->channels[$connection->app->id][$channelName]);
            });

        if (count(Arr::get($this->channels, $connection->app->id, [])) === 0) {
            unset($this->channels[$connection->app->id]);
        }
    }

    /**
     * Get the channel class by the channel name.
     *
     * @param  string  $channelName
     * @return string
     */
    protected function determineChannelClass(string $channelName): string
    {
        if (Str::startsWith($channelName, 'private-')) {
            return PrivateChannel::class;
        }

        if (Str::startsWith($channelName, 'presence-')) {
            return PresenceChannel::class;
        }

        return Channel::class;
    }
}
