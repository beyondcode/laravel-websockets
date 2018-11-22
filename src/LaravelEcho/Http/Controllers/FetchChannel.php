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
        $this->verifySignature($request);

        foreach ($request->json()->get('channels', []) as $channelId) {
            $channel = $this->channelManager->find($request->appId, $channelId);

            optional($channel)->broadcast([
                'channel' => $channelId,
                'event' => $request->json()->get('name'),
                'data' => $request->json()->get('data'),
            ]);
        }

        return $request->json()->all();
    }

    protected function verifySignature(Request $request)
    {
        $bodyMd5 = md5($request->getContent());

        $signature =
            "POST\n/apps/{$request->appId}/events\n".
            "auth_key={$request->auth_key}".
            "&auth_timestamp={$request->auth_timestamp}".
            "&auth_version={$request->auth_version}".
            "&body_md5={$bodyMd5}";

        $authSignature = hash_hmac('sha256', $signature, config('broadcasting.connections.pusher.secret'));

        if ($authSignature !== $request->get('auth_signature')) {
            throw new HttpException(401, 'Invalid authentication signature.');
        }
    }
}