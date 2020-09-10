<?php

namespace BeyondCode\LaravelWebSockets\Test\Dashboard;

use BeyondCode\LaravelWebSockets\Test\Models\User;
use BeyondCode\LaravelWebSockets\Test\TestCase;

class SendMessageTest extends TestCase
{
    public function test_can_send_message()
    {
        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.event'), [
                'appId' => '1234',
                'channel' => 'test-channel',
                'event' => 'some-event',
                'data' => json_encode(['data' => 'yes']),
            ])
            ->seeJson([
                'ok' => true,
            ]);

        if (method_exists($this->channelManager, 'getPublishClient')) {
            $this->channelManager
                ->getPublishClient()
                ->assertCalledWithArgs('publish', [
                    $this->channelManager->getRedisKey('1234', 'test-channel'),
                    json_encode([
                        'channel' => 'test-channel',
                        'event' => 'some-event',
                        'data' => ['data' => 'yes'],
                        'appId' => '1234',
                        'serverId' => $this->channelManager->getServerId(),
                    ]),
                ]);
        }
    }

    public function test_cant_send_message_for_invalid_app()
    {
        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.event'), [
                'appId' => '9999',
                'channel' => 'test-channel',
                'event' => 'some-event',
                'data' => json_encode(['data' => 'yes']),
            ])
            ->assertResponseStatus(422);
    }
}
