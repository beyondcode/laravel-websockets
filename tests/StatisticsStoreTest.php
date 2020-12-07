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

        $this->pusherServer->onClose($rick);
        $this->pusherServer->onClose($morty);

        $this->statisticsCollector->save();

        $this->assertCount(2, $records = $this->statisticsStore->getRecords());

        $this->assertEquals('2', $records[1]['peak_connections_count']);

        $this->statisticsCollector->save();

        // The last one should not generate any more records
        // since the current state is empty.
        $this->assertCount(2, $records = $this->statisticsStore->getRecords());
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

        $this->pusherServer->onClose($rick);
        $this->pusherServer->onClose($morty);

        $this->statisticsCollector->save();

        $this->assertCount(2, $records = $this->statisticsStore->getRecords());

        $this->assertEquals('2', $records[1]['peak_connections_count']);

        $this->statisticsCollector->save();

        // The last one should not generate any more records
        // since the current state is empty.
        $this->assertCount(2, $records = $this->statisticsStore->getRecords());
    }

    public function test_store_statistics_on_presence_channel()
    {
        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);
        $pickleRick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);

        $this->statisticsCollector->save();

        $this->assertCount(1, $records = $this->statisticsStore->getRecords());

        $this->assertEquals('3', $records[0]['peak_connections_count']);
        $this->assertEquals('3', $records[0]['websocket_messages_count']);
        $this->assertEquals('0', $records[0]['api_messages_count']);

        $this->pusherServer->onClose($rick);
        $this->pusherServer->onClose($morty);
        $this->pusherServer->onClose($pickleRick);

        $this->statisticsCollector->save();

        $this->assertCount(2, $records = $this->statisticsStore->getRecords());

        $this->assertEquals('3', $records[1]['peak_connections_count']);

        $this->statisticsCollector->save();

        // The last one should not generate any more records
        // since the current state is empty.
        $this->assertCount(2, $records = $this->statisticsStore->getRecords());
    }
}
