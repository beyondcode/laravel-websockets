<?php

namespace BeyondCode\LaravelWebSockets\Console\Commands;

use BeyondCode\LaravelWebSockets\Facades\StatisticsStore;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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

        $timestamp = now()->subDays($days);

        /*
         * Laravel projects may be configured to use CarbonImmutable,
         * so now() would give us an immutable instance,
         * but StatisticsStore expects an actual Carbon instance,
         * so we'll convert it here.
         */
        if ($timestamp instanceof CarbonImmutable) {
            $timestamp = new Carbon($timestamp);
        }

        $amountDeleted = StatisticsStore::delete(
            $timestamp, $this->argument('appId')
        );

        $this->info("Deleted {$amountDeleted} record(s) from the WebSocket statistics storage.");
    }
}
