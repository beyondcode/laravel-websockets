<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use Pusher\Pusher;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;

class SendMessage
{
    /** @var \Illuminate\Contracts\Config\Repository */
    protected $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'appId' => ['required', new AppId()],
            'key' => 'required',
            'secret' => 'required',
            'channel' => 'required',
            'event' => 'required',
            'data' => 'json',
        ]);

        $this->getPusherBroadcaster($validated)->broadcast(
            [$validated['channel']],
            $validated['event'],
            json_decode($validated['data'], true)
        );

        return 'ok';
    }

    protected function getPusherBroadcaster(array $validated): PusherBroadcaster
    {
        $pusher = new Pusher(
            $validated['key'],
            $validated['secret'],
            $validated['appId'],
            $this->config->get('broadcasting.connections.pusher.options', [])
        );

        return new PusherBroadcaster($pusher);
    }
}
