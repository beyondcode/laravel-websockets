<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;
use Illuminate\Console\Command;
use BeyondCode\LaravelWebSockets\Server\WebSocketServer;

use React\EventLoop\Factory as LoopFactory;

class StartWebSocketServer extends Command
{
    protected $signature = 'websocket:start {--host=0.0.0.0} {--port=6001} ';

    protected $description = 'Start the Laravel WebSocket Server';

    public function handle()
    {
        // TODO: add an option to not start the echo server
        WebSocketRouter::echo();

        // TODO: add flag for verbose mode, to send more things to console

        $websocketServer = $this->createWebsocketServer();
        $websocketServer->run();
    }

    protected function createWebsocketServer(): WebSocketServer
    {
        $routes = WebSocketRouter::getRoutes();

        $loop = LoopFactory::create();

        $loop->futureTick(function () {
            $this->info('Started the WebSocket server on port '.$this->option('port'));
        });

        return (new WebSocketServer($routes))
            ->setHost($this->option('host'))
            ->setPort($this->option('port'))
            ->setConsoleOutput($this->output)
            ->enableLogging(config('app.debug'))
            ->setLoop($loop);
    }
}