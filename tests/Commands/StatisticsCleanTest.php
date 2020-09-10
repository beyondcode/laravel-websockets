<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\Test\TestCase;

class StatisticsCleanTest extends TestCase
{
    public function test_clean_statistics_for_app_id()
    {
        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel'], 'TestKey2');

        $this->statisticsCollector->save();

        $this->assertCount(2, $records = $this->statisticsStore->getRecords());

        foreach ($this->statisticsStore->getRawRecords() as $record) {
            $record->update(['created_at' => now()->subDays(10)]);
        };

        $this->artisan('websockets:clean', [
            'appId' => '12345',
            '--days' => 1,
        ]);

        $this->assertCount(1, $records = $this->statisticsStore->getRecords());
    }

    public function test_clean_statistics_older_than_given_days()
    {
        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel'], 'TestKey2');

        $this->statisticsCollector->save();

        $this->assertCount(2, $records = $this->statisticsStore->getRecords());

        foreach ($this->statisticsStore->getRawRecords() as $record) {
            $record->update(['created_at' => now()->subDays(10)]);
        };

        $this->artisan('websockets:clean', ['--days' => 1]);

        $this->assertCount(0, $records = $this->statisticsStore->getRecords());
    }
}
