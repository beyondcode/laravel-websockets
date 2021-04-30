<?php

namespace BeyondCode\LaravelWebSockets\Test\Dashboard;

use BeyondCode\LaravelWebSockets\Test\Mocks\SignedMessage;
use BeyondCode\LaravelWebSockets\Test\Models\User;
use BeyondCode\LaravelWebSockets\Test\TestCase;

class AuthTest extends TestCase
{
    public function test_can_authenticate_dashboard_over_channel()
    {
        $connection = $this->newActiveConnection(['test-channel']);

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

    public function test_can_authenticate_dashboard_over_private_channel()
    {
        $connection = $this->newConnection();

        $this->pusherServer->onOpen($connection);

        $message = new SignedMessage([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'private-channel',
            ],
        ], $connection, 'private-channel');

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

    public function test_can_authenticate_dashboard_over_presence_channel()
    {
        $connection = $this->newConnection();

        $this->pusherServer->onOpen($connection);

        $user = json_encode([
            'user_id' => 1,
            'user_info' => [
                'name' => 'Rick',
            ],
        ]);

        $message = new SignedMessage([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'presence-channel',
                'channel_data' => $user,
            ],
        ], $connection, 'presence-channel', $user);

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
