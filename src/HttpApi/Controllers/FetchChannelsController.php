<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;

class FetchChannelsController extends Controller
{
    /** @var ReplicationInterface */
    protected $replication;

    public function __construct(ChannelManager $channelManager, ReplicationInterface $replication)
    {
        parent::__construct($channelManager);

        $this->replication = $replication;
    }

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
        $channelNames = $channels->map(function (PresenceChannel $channel) use ($request) {
            return $channel->getChannelName();
        })->toArray();

        // We ask the replication backend to get us the member count per channel
        $memberCounts = $this->replication->channelMemberCounts($request->appId, $channelNames);

        // We return a promise since the backend runs async. We get $counts back
        // as a key-value array of channel names and their member count.
        return $memberCounts->then(function (array $counts) use ($channels, $attributes) {
            return $this->collectUserCounts($channels, $attributes, function (PresenceChannel $channel) use ($counts) {
                return $counts[$channel->getChannelName()];
            });
        });
    }

    protected function collectUserCounts(Collection $channels, array $attributes, callable $transformer)
    {
        return [
            'channels' => $channels->map(function (PresenceChannel $channel) use ($transformer, $attributes) {
                $info = new \stdClass;
                if (in_array('user_count', $attributes)) {
                    $info->user_count = $transformer($channel);
                }

                return $info;
            })->toArray() ?: new \stdClass,
        ];
    }
}
