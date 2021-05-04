<?php

namespace BeyondCode\LaravelWebSockets\Api\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function collect;

class FetchUsers extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if (! Str::startsWith($request->channelName, 'presence-')) {
            return new HttpException(400, "Invalid presence channel `{$request->channelName}`");
        }

        return $this->channelManager
            ->getChannelMembers($request->appId, $request->channelName)
            ->then(function ($members) {
                $users = collect($members)->map(function ($user) {
                    return ['id' => $user->user_id];
                })->values()->toArray();

                return [
                    'users' => $users,
                ];
            });
    }
}
