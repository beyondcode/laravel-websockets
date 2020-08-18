<?php

namespace BeyondCode\LaravelWebSockets\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\InteractsWithTime;

class RestartWebSocketServer extends Command
{
    use InteractsWithTime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:restart';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Restart the Laravel WebSocket Server';

    /**
     * Run the command.
     *
     * @return void
     */
    public function handle()
    {
        Cache::forever('beyondcode:websockets:restart', $this->currentTime());

        $this->info('Broadcasting WebSocket server restart signal.');
    }
}
