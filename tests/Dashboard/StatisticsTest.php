<?php

namespace BeyondCode\LaravelWebSockets\Test\Dashboard;

use BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger;
use BeyondCode\LaravelWebSockets\Test\Models\User;
use BeyondCode\LaravelWebSockets\Test\TestCase;

class StatisticsTest extends TestCase
{
    public function test_can_get_statistics()
    {
        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel']);

        $this->statisticsCollector->save();

        $response = $this->actingAs(factory(User::class)->create())
            ->json('GET', route('laravel-websockets.statistics', ['appId' => '1234']))
            ->assertResponseOk()
            ->seeJsonStructure([
                'peak_connections' => ['x', 'y'],
                'websocket_messages_count' => ['x', 'y'],
                'api_messages_count' => ['x', 'y'],
            ]);
    }

    public function test_cant_get_statistics_for_invalid_app_id()
    {
        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel']);

        $this->statisticsCollector->save();

        $this->actingAs(factory(User::class)->create())
            ->json('GET', route('laravel-websockets.statistics', ['appId' => 'not_found']))
            ->seeJson([
                'peak_connections' => ['x' => [], 'y' => []],
                'websocket_messages_count' => ['x' => [], 'y' => []],
                'api_messages_count' => ['x' => [], 'y' => []],
            ]);
    }
}
