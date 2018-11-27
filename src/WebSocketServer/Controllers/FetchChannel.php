<?php

namespace BeyondCode\LaravelWebSockets\WebSocketServer\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannel extends EchoController
{
    public function __invoke(Request $request)
    {
        $channel = $this->channelManager->find($request->appId, $request->channelName);

        if (is_null($channel)) {
            throw new HttpException(404, "Unknown channel `{$request->channelName}`.");
        }

        return $channel->toArray();
    }
}