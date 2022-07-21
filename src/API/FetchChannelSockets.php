<?php

namespace BeyondCode\LaravelWebSockets\API;

use BeyondCode\LaravelWebSockets\Channels\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannelSockets extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if ($request->has('starts_with')) {
            if (! Str::startsWith($request->starts_with, ['presence-', 'private-'])) {
                throw new HttpException(400, 'Requests must be limited to presence and private channels in order to fetch channel data');
            }
        }

        return $this->channelManager
            ->getChannelSockets($request->appId)
            ->then(function ($channels) use ($request) {
                if (count($channels)) {

                    if ($request->has('starts_with')) {
                        $channels = $channels->filter(function ($channel, $channelName) use ($request) {
                            return Str::startsWith($channelName, $request->starts_with);
                        });
                    }

                    $channelNames = $channels->keys()->all();

                    return $this->channelManager
                        ->getChannelsSocketsCount($request->appId, $channelNames)
                        ->then(function ($counts) use ($channels) {
                            return [
                                'channels' => $counts ?: new stdClass,
                            ];
                        });
                }
                return [];
            });
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
//    public function __invoke(Request $request)
//    {
//        if ($request->has('starts_with')) {
//            if (! (Str::startsWith($request->starts_with, 'presence-') || Str::startsWith($request->starts_with, 'private-'))) {
//                throw new HttpException(400, 'Request must be limited to presence and private channels in order to fetch channel data');
//            }
//        }
//
//        return $this->channelManager
//            ->getChannelSockets($request->appId)
//            ->then(function ($channels) use ($request) {
//                $channels = collect($channels)->keyBy(function ($channel) {
//                    return $channel instanceof Channel
//                        ? $channel->getName()
//                        : $channel;
//                });
//
//                if ($request->has('starts_with')) {
//                    $channels = $channels->filter(function ($channel, $channelName) use ($request) {
//                        return Str::startsWith($channelName, $request->starts_with);
//                    });
//                }
//
//                $channelNames = $channels->map(function ($channel) {
//                    return $channel instanceof Channel
//                        ? $channel->getName()
//                        : $channel;
//                })->toArray();
//
//                return $this->channelManager
//                    ->getChannelsSocketsCount($request->appId, $channelNames)
//                    ->then(function ($counts) use ($channels) {
//                        $channels = $channels->map(function ($channel) use ($counts) {
//                            $info = new stdClass;
//
//                            $channelName = $channel instanceof Channel
//                                ? $channel->getName()
//                                : $channel;
//
//                            $info->count = $counts[$channelName];
//
//                            return $info;
//                        })->sortBy(function ($content, $name) {
//                            return $name;
//                        })->all();
//
//                        return [
//                            'channels' => $channels ?: new stdClass,
//                        ];
//                    });
//            });
//    }
}
