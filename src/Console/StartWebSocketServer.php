<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;
use Illuminate\Console\Command;
use BeyondCode\LaravelWebSockets\Server\WebSocketServer;

use React\EventLoop\Factory as LoopFactory;

class StartWebSocketServer extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:start {--port=6001}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Laravel WebSocket Server';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $loop = LoopFactory::create();

        $loop->futureTick(function () {
            $this->info('Started the WebSocket server on port '.$this->option('port'));
        });

        // TODO: add an option to not start the echo server
        WebSocketRouter::echo();

        (new WebsocketServer($this->option('port'), '0.0.0.0', $loop))->run();
    }
}