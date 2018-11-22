<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\InvalidSignatureException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannel extends EchoController
{
    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    public function __invoke(Request $request)
    {
        $channel = $this->channelManager->find($request->appId, $request->channelName);

        if (is_null($channel)) {
            throw new HttpException(404, 'Unknown channel "'.$request->channelName.'"');
        }

        return $channel->toArray();
    }
}