<?php

namespace BeyondCode\LaravelWebSockets\Tests\Dashboard;

use BeyondCode\LaravelWebSockets\Tests\Models\User;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class SendMessageTest extends TestCase
{
    /** @test */
    public function can_send_message()
    {
        $this->skipOnRedisReplication();

        // Because the Pusher server is not active,
        // we expect it to turn out ok: false.

        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.event'), [
                'appId' => '1234',
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'channel' => 'test-channel',
                'event' => 'some-event',
                'data' => json_encode(['data' => 'yes']),
            ])
            ->seeJson([
                'ok' => false,
            ]);
    }

    /** @test */
    public function can_send_message_on_redis_replication()
    {
        $this->skipOnLocalReplication();

        // Because the Pusher server is not active,
        // we expect it to turn out ok: false.
        // However, the driver is set to redis,
        // so Redis would take care of this
        // and stream the message to all active servers instead.

        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.event'), [
                'appId' => '1234',
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'channel' => 'test-channel',
                'event' => 'some-event',
                'data' => json_encode(['data' => 'yes']),
            ])
            ->seeJson([
                'exception' => 'Failed to connect to Pusher.',
                'ok' => false,
            ]);
    }

    /** @test */
    public function cant_send_message_for_invalid_app()
    {
        $this->skipOnRedisReplication();

        // Because the Pusher server is not active,
        // we expect it to turn out ok: false.

        $this->actingAs(factory(User::class)->create())
            ->json('POST', route('laravel-websockets.event'), [
                'appId' => '9999',
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'channel' => 'test-channel',
                'event' => 'some-event',
                'data' => json_encode(['data' => 'yes']),
            ])
            ->assertResponseStatus(422);
    }
}
