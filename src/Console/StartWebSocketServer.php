<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use BeyondCode\LaravelWebSockets\Server\Logger\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\StatisticsLogger as StatisticsLoggerInterface;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Buzz\Browser;
use Illuminate\Console\Command;
use BeyondCode\LaravelWebSockets\Server\WebSocketServerFactory;

use React\EventLoop\Factory as LoopFactory;
use React\Socket\Connector;

class StartWebSocketServer extends Command
{
    protected $signature = 'websockets:serve {--host=0.0.0.0} {--port=6001} ';

    protected $description = 'Start the Laravel WebSocket Server';

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    public function __construct()
    {
        parent::__construct();

        $this->loop = LoopFactory::create();
    }

    public function handle()
    {
        $this
            ->configureStatisticsLogger()
            ->configureHttpLogger()
            ->configureMessageLogger()
            ->configureConnectionLogger()
            ->registerEchoRoutes()
            ->startWebSocketServer();
    }

    protected function configureStatisticsLogger()
    {
        $connector = new Connector($this->loop, [
            'dns' => '127.0.0.1'
        ]);

        $browser = new Browser($this->loop, $connector);


        app()->singleton(StatisticsLoggerInterface::class, function() use ($browser) {
            return new HttpStatisticsLogger(app(ChannelManager::class), $browser);
        });

        $this->loop->addPeriodicTimer(5, function() {
            StatisticsLogger::save();
        });

        return $this;
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
        app()->singleton(WebsocketsLogger::class, function() {
            return (new WebsocketsLogger($this->output))
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
            ->setLoop($this->loop)
            ->useRoutes($routes)
            ->setHost($this->option('host'))
            ->setPort($this->option('port'))
            ->setConsoleOutput($this->output)
            ->createServer()
            ->run();
    }
}
