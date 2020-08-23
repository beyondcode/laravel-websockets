<?php

namespace BeyondCode\LaravelWebSockets\Tests\Commands;

use Artisan;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CleanStatisticsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 00, 00, 00));

        $this->app['config']->set('websockets.statistics.delete_statistics_older_than_days', 31);
    }

    /** @test */
    public function it_can_clean_the_statistics()
    {
        Collection::times(60)->each(function (int $index) {
            WebSocketsStatisticsEntry::create([
                'app_id' => 'app_id',
                'peak_connection_count' => 1,
                'websocket_message_count' => 2,
                'api_message_count' => 3,
                'created_at' => Carbon::now()->subDays($index)->startOfDay(),
            ]);
        });

        $this->assertCount(60, WebSocketsStatisticsEntry::all());

        Artisan::call('websockets:clean');

        $this->assertCount(31, WebSocketsStatisticsEntry::all());

        $cutOffDate = Carbon::now()->subDays(31)->format('Y-m-d H:i:s');

        $this->assertCount(0, WebSocketsStatisticsEntry::where('created_at', '<', $cutOffDate)->get());
    }

    /** @test */
    public function it_can_clean_the_statistics_for_app_id_only()
    {
        Collection::times(60)->each(function (int $index) {
            WebSocketsStatisticsEntry::create([
                'app_id' => 'app_id',
                'peak_connection_count' => 1,
                'websocket_message_count' => 2,
                'api_message_count' => 3,
                'created_at' => Carbon::now()->subDays($index)->startOfDay(),
            ]);
        });

        Collection::times(60)->each(function (int $index) {
            WebSocketsStatisticsEntry::create([
                'app_id' => 'app_id2',
                'peak_connection_count' => 1,
                'websocket_message_count' => 2,
                'api_message_count' => 3,
                'created_at' => Carbon::now()->subDays($index)->startOfDay(),
            ]);
        });

        $this->assertCount(120, WebSocketsStatisticsEntry::all());

        Artisan::call('websockets:clean', ['appId' => 'app_id']);

        $this->assertCount(91, WebSocketsStatisticsEntry::all());
    }
}
