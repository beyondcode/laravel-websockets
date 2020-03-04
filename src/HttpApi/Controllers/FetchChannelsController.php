<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannelsController extends Controller
{
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

        return [
            'channels' => $channels->map(function ($channel) use ($attributes) {
                $info = new \stdClass;
                if (in_array('user_count', $attributes)) {
                    $info->user_count = count($channel->getUsers());
                }

                return $info;
            })->toArray() ?: new \stdClass,
        ];
    }
}
