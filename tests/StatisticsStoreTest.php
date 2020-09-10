<?php

namespace BeyondCode\LaravelWebSockets\Test;

class StatisticsStoreTest extends TestCase
{
    public function test_store_statistics_on_public_channel()
    {
        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel']);

        $this->statisticsCollector->save();

        $this->assertCount(1, $records = $this->statisticsStore->getRecords());

        $this->assertEquals('2', $records[0]['peak_connections_count']);
        $this->assertEquals('2', $records[0]['websocket_messages_count']);
        $this->assertEquals('0', $records[0]['api_messages_count']);
    }

    public function test_store_statistics_on_private_channel()
    {
        $rick = $this->newPrivateConnection('private-channel');
        $morty = $this->newPrivateConnection('private-channel');

        $this->statisticsCollector->save();

        $this->assertCount(1, $records = $this->statisticsStore->getRecords());

        $this->assertEquals('2', $records[0]['peak_connections_count']);
        $this->assertEquals('2', $records[0]['websocket_messages_count']);
        $this->assertEquals('0', $records[0]['api_messages_count']);
    }

    public function test_store_statistics_on_presence_channel()
    {
        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        $this->statisticsCollector->save();

        $this->assertCount(1, $records = $this->statisticsStore->getRecords());

        $this->assertEquals('2', $records[0]['peak_connections_count']);
        $this->assertEquals('2', $records[0]['websocket_messages_count']);
        $this->assertEquals('0', $records[0]['api_messages_count']);
    }
}
