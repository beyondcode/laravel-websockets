<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;

class FetchChannelsController extends Controller
{
    public function __invoke(Request $request)
    {
        $channels = Collection::make($this->channelManager->getChannels($request->appId))->filter(function ($channel) {
            return $channel instanceof PresenceChannel;
        });

        if ($request->has('filter_by_prefix')) {
            $channels = $channels->filter(function ($channel, $channelName) use ($request) {
                return starts_with($channelName, $request->filter_by_prefix);
            });
        }

        return [
            'channels' => $channels->map(function ($channel) {
                return [
                    'user_count' => count($channel->getUsers()),
                ];
            })->toArray() ?: new \stdClass,
        ];
    }
}
