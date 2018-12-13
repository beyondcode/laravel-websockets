<?php

namespace BeyondCode\LaravelWebSockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Events\MemberAdded;
use BeyondCode\LaravelWebSockets\Events\MemberRemoved;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Illuminate\Support\Facades\Event;

class PresenceChannelTest extends TestCase
{
    /** @test */
    public function clients_need_valid_auth_signatures_to_join_presence_channels()
    {
        $this->expectException(InvalidSignature::class);

        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data'  => [
                'auth'    => 'invalid',
                'channel' => 'presence-channel',
            ],
        ]));

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);
    }

    /** @test */
    public function clients_with_valid_auth_signatures_can_join_presence_channels()
    {
        Event::fake();

        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id'   => 1,
            'user_info' => [
                'name' => 'Marcel',
            ],
        ];

        $signature = "{$connection->socketId}:presence-channel:" . json_encode($channelData);

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data'  => [
                'auth'         => $connection->app->key . ':' . hash_hmac('sha256', $signature, $connection->app->secret),
                'channel'      => 'presence-channel',
                'channel_data' => json_encode($channelData),
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
        ]);

        Event::assertDispatched(MemberAdded::class, function ($event) use ($channelData) {
            return $event->channel === 'presence-channel' &&
            $event->data == json_decode(json_encode($channelData));
        });
    }

    /** @test */
    public function clients_can_unsubscribe_from_channels()
    {
        Event::fake();

        $connection = $this->joinPresenceChannel('presence-channel');

        $channel = $this->getChannel($connection, 'presence-channel');

        $message = new Message(json_encode([
            'event' => 'pusher:unsubscribe',
            'data'  => [
                'channel' => 'presence-channel',
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        Event::assertDispatched(MemberRemoved::class, function ($event) {
            return $event->channel === 'presence-channel' && $event->data == ['user_id' => 1];
        });
    }
}
