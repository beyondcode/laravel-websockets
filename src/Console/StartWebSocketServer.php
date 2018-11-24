<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;
use BeyondCode\LaravelWebSockets\Server\Logger;
use Illuminate\Console\Command;
use BeyondCode\LaravelWebSockets\Server\WebSocketServer;

use React\EventLoop\Factory as LoopFactory;

class StartWebSocketServer extends Command
{
    protected $signature = 'websocket:start {--host=0.0.0.0} {--port=6001} ';

    protected $description = 'Start the Laravel WebSocket Server';

    public function handle()
    {
        $this
            ->configureLogger()
            ->registerEchoRoutes()
            ->startWebSocketServer();
    }

    protected function configureLogger()
    {
        app()->singleton(Logger::class, function() {
            return (new Logger($this->output))
                ->enable(config('app.debug'))
                //TODO: use real option
                ->verbose($this->hasOption('vvv'));
        });

        return $this;
    }

    protected function registerEchoRoutes()
    {
        WebSocketRouter::echo();

        return $this;
    }

    protected function startWebSocketServer()
    {
        $routes = WebSocketRouter::getRoutes();

        $loop = LoopFactory::create();

        $loop->futureTick(function () {
            $this->info("Started the WebSocket server on port {$this->option('port')}");
        });

        /** 🎩 Start the magic 🎩 */
        return (new WebSocketServer($routes))
            ->setHost($this->option('host'))
            ->setPort($this->option('port'))
            ->setConsoleOutput($this->output)
            ->setLoop($loop)
            ->run();
    }
}
