<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use Illuminate\Http\Request;

class EventController extends EchoController
{

    /** @var ChannelManager */
    private $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    public function __invoke(Request $request)
    {
        foreach ($request->json()->get('channels', []) as $channelId) {
            $channel = $this->channelManager->find($channelId);

            $channel->broadcast([
                'channel' => $channelId,
                'event' => $request->json()->get('name'),
                'data' => $request->json()->get('data'),
            ]);
        }
        return $request->json()->all();
    }
}