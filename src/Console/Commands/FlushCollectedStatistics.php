<?php

namespace BeyondCode\LaravelWebSockets\Console\Commands;

use BeyondCode\LaravelWebSockets\Facades\StatisticsCollector;
use Illuminate\Console\Command;

class FlushCollectedStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:flush';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Flush the collected statistics.';

    /**
     * Run the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Flushing the collected WebSocket Statistics...');

        StatisticsCollector::flush();

        $this->line('Flush complete!');
    }
}
