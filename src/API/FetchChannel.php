<?php

namespace BeyondCode\LaravelWebSockets\API;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannel extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $channel = $this->channelManager->find(
            $request->appId, $request->channelName
        );

        if (is_null($channel)) {
            return new HttpException(404, "Unknown channel `{$request->channelName}`.");
        }

        return $this->channelManager
            ->getGlobalConnectionsCount($request->appId, $request->channelName)
            ->then(function ($connectionsCount) use ($request) {
                // For the presence channels, we need a slightly different response
                // that need an additional call.
                if (Str::startsWith($request->channelName, 'presence-')) {
                    return $this->channelManager
                        ->getChannelsMembersCount($request->appId, [$request->channelName])
                        ->then(function ($channelMembers) use ($connectionsCount, $request) {
                            return [
                                'occupied' => $connectionsCount > 0,
                                'subscription_count' => $connectionsCount,
                                'user_count' => $channelMembers[$request->channelName] ?? 0,
                            ];
                        });
                }

                // For the rest of the channels, we might as well
                // send the basic response with the subscriptions count.
                return [
                    'occupied' => $connectionsCount > 0,
                    'subscription_count' => $connectionsCount,
                ];
            });
    }
}
