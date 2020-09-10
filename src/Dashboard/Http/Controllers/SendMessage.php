<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Rules\AppId;
use Exception;
use Illuminate\Http\Request;

class SendMessage
{
    /**
     * Send the message to the requested channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \BeyondCode\LaravelWebSockets\Contracts\ChannelManager  $channelManager
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, ChannelManager $channelManager)
    {
        $request->validate([
            'appId' => ['required', new AppId],
            'channel' => 'required|string',
            'event' => 'required|string',
            'data' => 'required|json',
        ]);

        $payload = [
            'channel' => $request->channel,
            'event' => $request->event,
            'data' => json_decode($request->data, true),
        ];

        // Here you can use the ->find(), even if the channel
        // does not exist on the server. If it does not exist,
        // then the message simply will get broadcasted
        // across the other servers.
        $channel = $channelManager->find(
            $request->appId, $request->channel
        );

        if ($channel) {
            $channel->broadcastToEveryoneExcept(
                (object) $payload,
                null,
                $request->appId
            );
        } else {
            $channelManager->broadcastAcrossServers(
                $request->appId, $request->channel, (object) $payload
            );
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
