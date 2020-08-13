<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebSocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class WebSocketsStatisticsControllerTest extends TestCase
{
    /** @test */
    public function it_can_store_statistics()
    {
        $this->post(
            action([WebSocketStatisticsEntriesController::class, 'store']),
            array_merge($this->payload(), [
                'secret' => config('websockets.apps.0.secret'),
            ])
        );

        $entries = WebSocketsStatisticsEntry::get();

        $this->assertCount(1, $entries);

        $actual = $entries->first()->attributesToArray();

        foreach ($this->payload() as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertSame($value, $actual[$key]);
        }
    }

    protected function payload(): array
    {
        return [
            'app_id' => config('websockets.apps.0.id'),
            'peak_connection_count' => '1',
            'websocket_message_count' => '2',
            'api_message_count' => '3',
        ];
    }
}
