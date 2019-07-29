<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;

class FetchUsersController extends Controller
{
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
