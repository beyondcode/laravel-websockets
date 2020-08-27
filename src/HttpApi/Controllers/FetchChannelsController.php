<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannelsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $attributes = [];

        if ($request->has('info')) {
            $attributes = explode(',', trim($request->info));

            if (in_array('user_count', $attributes) && ! Str::startsWith($request->filter_by_prefix, 'presence-')) {
                throw new HttpException(400, 'Request must be limited to presence channels in order to fetch user_count');
            }
        }

        $channels = Collection::make($this->channelManager->getChannels($request->appId));

        if ($request->has('filter_by_prefix')) {
            $channels = $channels->filter(function ($channel, $channelName) use ($request) {
                return Str::startsWith($channelName, $request->filter_by_prefix);
            });
        }

        // We want to get the channel user count all in one shot when
        // using a replication backend rather than doing individual queries.
        // To do so, we first collect the list of channel names.
        $channelNames = $channels->map(function (PresenceChannel $channel) {
            return $channel->getChannelName();
        })->toArray();

        // We ask the replication backend to get us the member count per channel.
        // We get $counts back as a key-value array of channel names and their member count.
        return $this->replicator
            ->channelMemberCounts($request->appId, $channelNames)
            ->then(function (array $counts) use ($channels, $attributes) {
                $channels = $channels->map(function (PresenceChannel $channel) use ($counts, $attributes) {
                    $info = new stdClass;

                    if (in_array('user_count', $attributes)) {
                        $info->user_count = $counts[$channel->getChannelName()];
                    }

                    return $info;
                })->toArray();

                return [
                    'channels' => $channels ?: new stdClass,
                ];
            });
    }
}
