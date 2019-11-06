<?php

namespace BeyondCode\LaravelWebSockets\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\InteractsWithTime;

class RestartWebSocketServer extends Command
{
    use InteractsWithTime;

    protected $signature = 'websockets:restart';

    protected $description = 'Restart the Laravel WebSocket Server';

    public function handle()
    {
        Cache::forever('beyondcode:websockets:restart', $this->currentTime());

        $this->info('Broadcasting WebSocket server restart signal.');
    }
}
