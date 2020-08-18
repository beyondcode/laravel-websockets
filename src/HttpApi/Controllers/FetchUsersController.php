<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchUsersController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $channel = $this->channelManager->find($request->appId, $request->channelName);

        if (is_null($channel)) {
            throw new HttpException(404, 'Unknown channel "'.$request->channelName.'"');
        }

        if (! $channel instanceof PresenceChannel) {
            throw new HttpException(400, 'Invalid presence channel "'.$request->channelName.'"');
        }

        return $channel
            ->getUsers($request->appId)
            ->then(function (array $users) {
                return [
                    'users' => Collection::make($users)->map(function ($user) {
                        return ['id' => $user->user_id];
                    })->values(),
                ];
            });
    }
}
