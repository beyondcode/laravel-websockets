<?php

namespace BeyondCode\LaravelWebSockets\Tests\Dashboard;

use BeyondCode\LaravelWebSockets\Statistics\Logger\RedisStatisticsLogger;
use BeyondCode\LaravelWebSockets\Tests\Models\User;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class RedisStatisticsTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();
    }

    /** @test */
    public function can_get_statistics()
    {
        $connection = $this->getConnectedWebSocketConnection(['channel-1']);

        $logger = new RedisStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        );

        $logger->webSocketMessage($connection->app->id);
        $logger->apiMessage($connection->app->id);
        $logger->connection($connection->app->id);
        $logger->disconnection($connection->app->id);

        $logger->save();

        $this->actingAs(factory(User::class)->create())
            ->json('GET', route('laravel-websockets.statistics', ['appId' => '1234']))
            ->assertResponseOk()
            ->seeJsonStructure([
                'peak_connections' => ['x', 'y'],
                'websocket_message_count' => ['x', 'y'],
                'api_message_count' => ['x', 'y'],
            ]);
    }

    /** @test */
    public function cant_get_statistics_for_invalid_app_id()
    {
        $connection = $this->getConnectedWebSocketConnection(['channel-1']);

        $logger = new RedisStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        );

        $logger->webSocketMessage($connection->app->id);
        $logger->apiMessage($connection->app->id);
        $logger->connection($connection->app->id);
        $logger->disconnection($connection->app->id);

        $logger->save();

        $this->actingAs(factory(User::class)->create())
            ->json('GET', route('laravel-websockets.statistics', ['appId' => 'not_found']))
            ->seeJson([
                'peak_connections' => ['x' => [], 'y' => []],
                'websocket_message_count' => ['x' => [], 'y' => []],
                'api_message_count' => ['x' => [], 'y' => []],
            ]);
    }
}
