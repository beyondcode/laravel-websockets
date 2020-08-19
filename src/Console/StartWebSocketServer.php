<?php

namespace BeyondCode\LaravelWebSockets\Console;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use BeyondCode\LaravelWebSockets\PubSub\Drivers\LocalClient;
use BeyondCode\LaravelWebSockets\PubSub\Drivers\RedisClient;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\Server\Logger\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketsLogger;
use BeyondCode\LaravelWebSockets\Server\WebSocketServerFactory;
use BeyondCode\LaravelWebSockets\Statistics\DnsResolver;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\Statistics\Logger\StatisticsLogger as StatisticsLoggerInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Buzz\Browser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use React\Dns\Config\Config as DnsConfig;
use React\Dns\Resolver\Factory as DnsFactory;
use React\Dns\Resolver\ResolverInterface;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Connector;

class StartWebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:serve
        {--host=0.0.0.0}
        {--port=6001}
        {--statistics-interval= : Overwrite the statistics interval set in the config.}
        {--debug : Forces the loggers to be enabled and thereby overriding the APP_DEBUG setting.}
        {--test : Prepare the server, but do not start it.}
    ';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Start the Laravel WebSocket Server';

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
     * Track the last restart.
     *
     * @var int
     */
    protected $lastRestart;

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
        $this
            ->configureStatisticsLogger()
            ->configureHttpLogger()
            ->configureMessageLogger()
            ->configureConnectionLogger()
            ->configureRestartTimer()
            ->configurePubSub()
            ->registerRoutes()
            ->startWebSocketServer();
    }

    /**
     * Configure the statistics logger class.
     *
     * @return $this
     */
    protected function configureStatisticsLogger()
    {
        $this->laravel->singleton(StatisticsLoggerInterface::class, function () {
            $class = config('websockets.statistics.logger', \BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger::class);

            return new $class(
                $this->laravel->make(ChannelManager::class),
                $this->laravel->make(StatisticsDriver::class)
            );
        });

        $this->loop->addPeriodicTimer($this->option('statistics-interval') ?: config('websockets.statistics.interval_in_seconds'), function () {
            $this->line('Saving statistics...');

            StatisticsLogger::save();
        });

        return $this;
    }

    /**
     * Configure the HTTP logger class.
     *
     * @return $this
     */
    protected function configureHttpLogger()
    {
        $this->laravel->singleton(HttpLogger::class, function () {
            return (new HttpLogger($this->output))
                ->enable($this->option('debug') ?: config('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * Configure the logger for messages.
     *
     * @return $this
     */
    protected function configureMessageLogger()
    {
        $this->laravel->singleton(WebsocketsLogger::class, function () {
            return (new WebsocketsLogger($this->output))
                ->enable($this->option('debug') ?: config('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * Configure the connection logger.
     *
     * @return $this
     */
    protected function configureConnectionLogger()
    {
        $this->laravel->bind(ConnectionLogger::class, function () {
            return (new ConnectionLogger($this->output))
                ->enable(config('app.debug'))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * Configure the Redis PubSub handler.
     *
     * @return $this
     */
    public function configureRestartTimer()
    {
        $this->lastRestart = $this->getLastRestart();

        $this->loop->addPeriodicTimer(10, function () {
            if ($this->getLastRestart() !== $this->lastRestart) {
                $this->loop->stop();
            }
        });

        return $this;
    }

    /**
     * Configure the replicators.
     *
     * @return void
     */
    public function configurePubSub()
    {
        if (config('websockets.replication.driver', 'local') === 'local') {
            $this->laravel->singleton(ReplicationInterface::class, function () {
                return new LocalClient;
            });
        }

        if (config('websockets.replication.driver', 'local') === 'redis') {
            $this->laravel->singleton(ReplicationInterface::class, function () {
                return (new RedisClient)->boot($this->loop);
            });
        }

        $this->laravel
            ->get(ReplicationInterface::class)
            ->boot($this->loop);

        return $this;
    }

    /**
     * Register the routes.
     *
     * @return $this
     */
    protected function registerRoutes()
    {
        WebSocketsRouter::routes();

        return $this;
    }

    /**
     * Start the server.
     *
     * @return void
     */
    protected function startWebSocketServer()
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

        /* 🛰 Start the server 🛰  */
        $this->server->run();
    }

    /**
     * Build the server instance.
     *
     * @return void
     */
    protected function buildServer()
    {
        $this->server = new WebSocketServerFactory(
            $this->option('host'), $this->option('port')
        );

        $this->server = $this->server
            ->setLoop($this->loop)
            ->useRoutes(WebSocketsRouter::getRoutes())
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
        return Cache::get('beyondcode:websockets:restart', 0);
    }
}
