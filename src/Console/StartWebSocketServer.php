<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use BeyondCode\LaravelWebSockets\Server\Logger\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketLogger;
use Illuminate\Console\Command;
use BeyondCode\LaravelWebSockets\Server\WebSocketServerFactory;

use React\EventLoop\Factory as LoopFactory;

class StartWebSocketServer extends Command
{
    protected $signature = 'websockets:serve {--host=0.0.0.0} {--port=6001} ';

    protected $description = 'Start the Laravel WebSocket Server';

    public function handle()
    {
        $this
            ->configureHttpLogger()
            ->configureMessageLogger()
            ->configureConnectionLogger()
            ->registerEchoRoutes()
            ->startWebSocketServer();
    }

    protected function configureHttpLogger()
    {
        app()->singleton(HttpLogger::class, function() {
            return (new HttpLogger($this->output))
                ->enable(config('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    protected function configureMessageLogger()
    {
        app()->singleton(WebsocketLogger::class, function() {
            return (new WebsocketLogger($this->output))
                ->enable(config('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    protected function configureConnectionLogger()
    {
        app()->bind(ConnectionLogger::class, function() {
            return (new ConnectionLogger($this->output))
                ->enable(config('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    protected function registerEchoRoutes()
    {
        WebSocketsRouter::echo();

        return $this;
    }

    protected function startWebSocketServer()
    {
        $this->info("Starting the WebSocket server on port {$this->option('port')}...");

        $routes = WebSocketsRouter::getRoutes();

        /** 🛰 Start the server 🛰  */
        (new WebSocketServerFactory())
            ->useRoutes($routes)
            ->setHost($this->option('host'))
            ->setPort($this->option('port'))
            ->setConsoleOutput($this->output)
            ->createServer()
            ->run();
    }
}
