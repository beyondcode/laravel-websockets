<?php

namespace BeyondCode\LaravelWebSockets\Console\Commands;

use BeyondCode\LaravelWebSockets\Facades\StatisticsStore;
use Illuminate\Console\Command;

class CleanStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:clean
        {appId? : (optional) The app id that will be cleaned.}
        {--days= : Delete records older than this amount of days since now.}
    ';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Clean up old statistics from the WebSocket statistics storage.';

    /**
     * Run the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Cleaning WebSocket Statistics...');

        $days = $this->option('days') ?: config('statistics.delete_statistics_older_than_days');

        $amountDeleted = StatisticsStore::delete(
            now()->subDays($days), $this->argument('appId')
        );

        $this->info("Deleted {$amountDeleted} record(s) from the WebSocket statistics storage.");
    }
}
