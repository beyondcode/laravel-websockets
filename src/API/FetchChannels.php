<?php

namespace BeyondCode\LaravelWebSockets\API;

use BeyondCode\LaravelWebSockets\Channels\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannels extends Controller
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

        return $this->channelManager
            ->getGlobalChannels($request->appId)
            ->then(function ($channels) use ($request, $attributes) {
                $channels = collect($channels)->keyBy(function ($channel) {
                    return $channel instanceof Channel
                        ? $channel->getName()
                        : $channel;
                });

                if ($request->has('filter_by_prefix')) {
                    $channels = $channels->filter(function ($channel, $channelName) use ($request) {
                        return Str::startsWith($channelName, $request->filter_by_prefix);
                    });
                }

                $channelNames = $channels->map(function ($channel) {
                    return $channel instanceof Channel
                        ? $channel->getName()
                        : $channel;
                })->toArray();

                return $this->channelManager
                    ->getChannelsMembersCount($request->appId, $channelNames)
                    ->then(function ($counts) use ($channels, $attributes) {
                        $channels = $channels->map(function ($channel) use ($counts, $attributes) {
                            $info = new stdClass;

                            $channelName = $channel instanceof Channel
                                ? $channel->getName()
                                : $channel;

                            if (in_array('user_count', $attributes)) {
                                $info->user_count = $counts[$channelName];
                            }

                            return $info;
                        })->sortBy(function ($content, $name) {
                            return $name;
                        })->all();

                        return [
                            'channels' => $channels ?: new stdClass,
                        ];
                    });
            });
    }
}
