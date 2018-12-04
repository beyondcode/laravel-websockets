<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannelController extends Controller
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
