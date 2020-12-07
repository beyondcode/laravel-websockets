<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\Server\Exceptions\OriginNotAllowed;
use BeyondCode\LaravelWebSockets\Server\Exceptions\UnknownAppKey;

class ConnectionTest extends TestCase
{
    public function test_cannot_connect_with_a_wrong_app_key()
    {
        $this->expectException(UnknownAppKey::class);

        $this->newActiveConnection(['public-channel'], 'NonWorkingKey');
    }

    public function test_unconnected_app_cannot_store_statistics()
    {
        $this->expectException(UnknownAppKey::class);

        $this->newActiveConnection(['public-channel'], 'NonWorkingKey');

        $this->assertCount(0, $this->statisticsCollector->getStatistics());
    }

    public function test_origin_validation_should_fail_for_no_origin()
    {
        $this->expectException(OriginNotAllowed::class);

        $connection = $this->newConnection('TestOrigin');

        $this->pusherServer->onOpen($connection);
    }

    public function test_origin_validation_should_fail_for_wrong_origin()
    {
        $this->expectException(OriginNotAllowed::class);

        $connection = $this->newConnection('TestOrigin', ['Origin' => 'https://google.ro']);

        $this->pusherServer->onOpen($connection);
    }

    public function test_origin_validation_should_pass_for_the_right_origin()
    {
        $connection = $this->newConnection('TestOrigin', ['Origin' => 'https://test.origin.com']);

        $this->pusherServer->onOpen($connection);

        $connection->assertSentEvent('pusher:connection_established');
    }

    public function test_close_connection()
    {
        $connection = $this->newActiveConnection(['public-channel']);

        $this->channelManager->getGlobalChannels('1234')->then(function ($channels) {
            $this->assertCount(1, $channels);
        });

        $this->channelManager->getGlobalConnectionsCount('1234')->then(function ($total) {
            $this->assertEquals(1, $total);
        });

        $this->pusherServer->onClose($connection);

        $this->channelManager->getGlobalConnectionsCount('1234')->then(function ($total) {
            $this->assertEquals(0, $total);
        });

        $this->channelManager->getGlobalChannels('1234')->then(function ($channels) {
            $this->assertCount(0, $channels);
        });
    }

    public function test_websocket_exceptions_are_sent()
    {
        $connection = $this->newActiveConnection(['public-channel']);

        $this->pusherServer->onError($connection, new UnknownAppKey('NonWorkingKey'));

        $connection->assertSentEvent('pusher:error', [
            'data' => [
                'message' => 'Could not find app key `NonWorkingKey`.',
                'code' => 4001,
            ],
        ]);
    }

    public function test_capacity_limit()
    {
        $this->app['config']->set('websockets.apps.0.capacity', 2);

        $this->newActiveConnection(['test-channel']);
        $this->newActiveConnection(['test-channel']);

        $failedConnection = $this->newActiveConnection(['test-channel']);

        $failedConnection
            ->assertSentEvent('pusher:error', ['data' => ['message' => 'Over capacity', 'code' => 4100]])
            ->assertClosed();
    }

    public function test_close_all_new_connections_after_stating_the_server_does_not_accept_new_connections()
    {
        $this->newActiveConnection(['test-channel'])
            ->assertSentEvent('pusher:connection_established')
            ->assertSentEvent('pusher_internal:subscription_succeeded');

        $this->channelManager->declineNewConnections();

        $this->assertFalse(
            $this->channelManager->acceptsNewConnections()
        );

        $this->newActiveConnection(['test-channel'])
            ->assertNothingSent()
            ->assertClosed();
    }
}
