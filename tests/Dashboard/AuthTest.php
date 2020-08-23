<?php

namespace BeyondCode\LaravelWebSockets\Tests\Dashboard;

use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\Models\User;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class AuthTest extends TestCase
{
    /** @test */
    public function can_authenticate_dashboard_over_channel()
    {
        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $this->pusherServer->onOpen($connection);

        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.auth'), [
                'socket_id' => $connection->socketId,
                'channel_name' => 'test-channel',
            ], ['x-app-id' => '1234'])
            ->seeJsonStructure([
                'auth',
                'channel_data',
            ]);
    }

    /** @test */
    public function can_authenticate_dashboard_over_private_channel()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $signature = "{$connection->socketId}:private-channel";

        $hashedAppSecret = hash_hmac('sha256', $signature, $connection->app->secret);

        $message = new Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => "{$connection->app->key}:{$hashedAppSecret}",
                'channel' => 'private-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'private-channel',
        ]);

        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.auth'), [
                'socket_id' => $connection->socketId,
                'channel_name' => 'private-test-channel',
            ], ['x-app-id' => '1234'])
            ->seeJsonStructure([
                'auth',
            ]);
    }

    /** @test */
    public function can_authenticate_dashboard_over_presence_channel()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Marcel',
            ],
        ];

        $signature = "{$connection->socketId}:presence-channel:".json_encode($channelData);

        $message = new Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => 'presence-channel',
                'channel_data' => json_encode($channelData),
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.auth'), [
                'socket_id' => $connection->socketId,
                'channel_name' => 'presence-channel',
            ], ['x-app-id' => '1234'])
            ->seeJsonStructure([
                'auth',
            ]);
    }
}
