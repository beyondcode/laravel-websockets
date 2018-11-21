<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use Illuminate\Http\Request;

class EventController extends EchoController
{
    /** @var ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    public function __invoke(Request $request)
    {
       //TODO: verify the incoming request
        /*
         * array:6 [
  "appId" => "test"
  "auth_key" => ""
  "auth_signature" => "51e7ab9c1411aacf9a4c28001ffc3e7f5fe71db130ce08ac071ab49d737bcf52"
  "auth_timestamp" => "1542833998"
  "auth_version" => "1.0"
  "body_md5" => "816e28da10f4aedf0821865eddf55e7f"
]
         */
        foreach ($request->json()->get('channels', []) as $channelId) {
            $channel = $this->channelManager->find($request->appId, $channelId);

            $channel->broadcast([
                'channel' => $channelId,
                'event' => $request->json()->get('name'),
                'data' => $request->json()->get('data'),
            ]);
        }
        return $request->json()->all();
    }
}