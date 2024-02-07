<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Requests\StoreAppRequest;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;

use function Clue\React\Block\await;

class StoreApp
{
    /**
     * Show the configured apps.
     *
     * @param  StoreAppRequest  $request
     * @param  \BeyondCode\LaravelWebSockets\Contracts\AppManager  $apps
     * @return void
     */
    public function __invoke(StoreAppRequest $request, AppManager $apps)
    {
        $appData = [
            'id' => (string) Str::uuid(),
            'key' => (string) Str::uuid(),
            'secret' => (string) Str::uuid(),
            'name' => $request->get('name'),
            'enable_client_messages' => $request->has('enable_client_messages'),
            'enable_statistics' => $request->has('enable_statistics'),
            'allowed_origins' => $request->get('allowed_origins'),
        ];

        await($apps->createApp($appData), app(LoopInterface::class));

        return redirect()->route('laravel-websockets.apps');
    }
}
