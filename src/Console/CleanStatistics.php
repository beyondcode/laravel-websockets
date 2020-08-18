<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CleanStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:clean
        {appId? : (optional) The app id that will be cleaned.}
    ';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Clean up old statistics from the websocket log.';

    /**
     * Run the command.
     *
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver  $driver
     * @return void
     */
    public function handle(StatisticsDriver $driver)
    {
        $this->comment('Cleaning WebSocket Statistics...');

        $amountDeleted = $driver::delete($this->argument('appId'));

        $this->info("Deleted {$amountDeleted} record(s) from the WebSocket statistics.");
    }
}
