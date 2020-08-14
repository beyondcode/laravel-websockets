<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateUserInfoController extends Controller
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

        $user = Collection::make($channel->getUsers())->filter(function($user) use ($request) {
            return $user->user_id === (int) $request->userId;
        })->first();

        if (is_null($user)) {
            throw new HttpException(404, 'Unknown user "'.$request->userId.'"');
        }

        $channel->updateUserInfo($user->user_id, (object) $request->info);
    }
}
