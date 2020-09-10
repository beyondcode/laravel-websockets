<?php

namespace BeyondCode\LaravelWebSockets\Console\Commands;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector;
use BeyondCode\LaravelWebSockets\Facades\StatisticsCollector as StatisticsCollectorFacade;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use BeyondCode\LaravelWebSockets\Server\Loggers\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Loggers\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Loggers\WebSocketsLogger;
use BeyondCode\LaravelWebSockets\ServerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use React\EventLoop\Factory as LoopFactory;

class StartServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:serve
        {--host=0.0.0.0}
        {--port=6001}
        {--disable-statistics : Disable the statistics tracking.}
        {--statistics-interval= : The amount of seconds to tick between statistics saving.}
        {--debug : Forces the loggers to be enabled and thereby overriding the APP_DEBUG setting.}
        {--test : Prepare the server, but do not start it.}
    ';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Start the LaravelWebSockets server.';

    /**
     * Get the loop instance.
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * The Pusher server instance.
     *
     * @var \Ratchet\Server\IoServer
     */
    public $server;

    /**
     * Initialize the command.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->loop = LoopFactory::create();
    }

    /**
     * Run the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->configureLoggers();

        $this->configureManagers();

        $this->configureStatistics();

        $this->configureRestartTimer();

        $this->startServer();
    }

    /**
     * Configure the loggers used for the console.
     *
     * @return void
     */
    protected function configureLoggers()
    {
        $this->configureHttpLogger();
        $this->configureMessageLogger();
        $this->configureConnectionLogger();
    }

    /**
     * Register the managers that are not resolved
     * in the package service provider.
     *
     * @return void
     */
    protected function configureManagers()
    {
        $this->laravel->singleton(ChannelManager::class, function () {
            $mode = config('websockets.replication.mode', 'local');

            $class = config("websockets.replication.modes.{$mode}.channel_manager");

            return new $class($this->loop);
        });
    }

    /**
     * Register the Statistics Collectors that
     * are not resolved in the package service provider.
     *
     * @return void
     */
    protected function configureStatistics()
    {
        $this->laravel->singleton(StatisticsCollector::class, function () {
            $replicationMode = config('websockets.replication.mode', 'local');

            $class = config("websockets.replication.modes.{$replicationMode}.collector");

            return new $class;
        });

        $this->laravel->singleton(StatisticsStore::class, function () {
            $class = config('websockets.statistics.store');

            return new $class;
        });

        if (! $this->option('disable-statistics')) {
            $intervalInSeconds = $this->option('statistics-interval') ?: config('websockets.statistics.interval_in_seconds', 3600);

            $this->loop->addPeriodicTimer($intervalInSeconds, function () {
                $this->line('Saving statistics...');

                StatisticsCollectorFacade::save();
            });
        }
    }

    /**
     * Configure the restart timer.
     *
     * @return void
     */
    public function configureRestartTimer()
    {
        $this->lastRestart = $this->getLastRestart();

        $this->loop->addPeriodicTimer(10, function () {
            if ($this->getLastRestart() !== $this->lastRestart) {
                $this->loop->stop();
            }
        });
    }

    /**
     * Configure the HTTP logger class.
     *
     * @return void
     */
    protected function configureHttpLogger()
    {
        $this->laravel->singleton(HttpLogger::class, function () {
            return (new HttpLogger($this->output))
                ->enable($this->option('debug') ?: config('app.debug'))
                ->verbose($this->output->isVerbose());
        });
    }

    /**
     * Configure the logger for messages.
     *
     * @return void
     */
    protected function configureMessageLogger()
    {
        $this->laravel->singleton(WebSocketsLogger::class, function () {
            return (new WebSocketsLogger($this->output))
                ->enable($this->option('debug') ?: config('app.debug'))
                ->verbose($this->output->isVerbose());
        });
    }

    /**
     * Configure the connection logger.
     *
     * @return void
     */
    protected function configureConnectionLogger()
    {
        $this->laravel->bind(ConnectionLogger::class, function () {
            return (new ConnectionLogger($this->output))
                ->enable(config('app.debug'))
                ->verbose($this->output->isVerbose());
        });
    }

    /**
     * Start the server.
     *
     * @return void
     */
    protected function startServer()
    {
        $this->info("Starting the WebSocket server on port {$this->option('port')}...");

        $this->buildServer();

        // For testing, just boot up the server, run it
        // but exit after the next tick.
        if ($this->option('test')) {
            $this->loop->futureTick(function () {
                $this->loop->stop();
            });
        }

        $this->server->run();
    }

    /**
     * Build the server instance.
     *
     * @return void
     */
    protected function buildServer()
    {
        $this->server = new ServerFactory(
            $this->option('host'), $this->option('port')
        );

        $this->server = $this->server
            ->setLoop($this->loop)
            ->withRoutes(WebSocketsRouter::getRoutes())
            ->setConsoleOutput($this->output)
            ->createServer();
    }

    /**
     * Get the last time the server restarted.
     *
     * @return int
     */
    protected function getLastRestart()
    {
        return Cache::get(
            'beyondcode:websockets:restart', 0
        );
    }
}
