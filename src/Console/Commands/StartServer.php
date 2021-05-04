<?php

namespace BeyondCode\LaravelWebSockets\Console\Commands;

use Amp\Http\Server\HttpServer;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\Loop;
use BeyondCode\LaravelWebSockets\Contracts\Server;
use BeyondCode\LaravelWebSockets\Facades\StatisticsCollector as StatisticsCollectorFacade;
use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;
use BeyondCode\LaravelWebSockets\Server\Loggers\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Loggers\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Loggers\WebSocketsLogger;
use BeyondCode\LaravelWebSockets\ServerFactory;
use BeyondCode\LaravelWebSockets\Servers\ServerManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class StartServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:serve
        {--listen=0.0.0.0:6001,[::1]:6001 : Addresses to listen to, separated by comma (0.0.0.0:6001,[::1]:6001)}
        {--disable-statistics : Disable the statistics tracking.}
        {--statistics-interval= : The amount of seconds to tick between statistics saving.}
        {--server= : Uses a different server implementation}
        {--debug : Forces the loggers to be enabled, thereby overriding the APP_DEBUG setting.}
    ';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Start the Websockets server.';

    /**
     * Loop implementation.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\Loop
     */
    public $loop;

    /**
     * The server instance.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public $server;

    /**
     * Checks if the invoke server is already shutting down.
     *
     * @var bool
     */
    public $isShuttingDown = false;

    /**
     * Last time the server restarted.
     *
     * @var int
     */
    protected $lastRestart = 0;

    /**
     * Run the command.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Loop  $loop
     *
     * @return void
     */
    public function handle(Loop $loop): void
    {
        $this->loop = $loop;

//        $this->configureLoggers();

//        $this->configureManagers();

//        $this->configureStatistics();

//        $this->configureRestartTimer();

//        $this->configureRoutes();

//        $this->configurePcntlSignal();

//        $this->configurePongTracker();

        $this->startServer();
    }

    /**
     * Configure the loggers used for the console.
     *
     * @return void
     */
    protected function configureLoggers(): void
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
    protected function configureManagers(): void
    {
        $this->laravel->singleton(
            ChannelManager::class,
            function ($app) {
                $config = $app['config']['websockets'];
                $mode = $config['replication']['mode'] ?? 'local';

                $class = $config['replication']['modes'][$mode]['channel_manager'];

                return new $class();
            }
        );
    }

    /**
     * Register the Statistics Collectors that
     * are not resolved in the package service provider.
     *
     * @return void
     */
    protected function configureStatistics(): void
    {
        if (!$this->option('disable-statistics')) {
            $intervalInSeconds = $this->option('statistics-interval') ?: config(
                'websockets.statistics.interval_in_seconds',
                3600
            );

            $this->loop->repeat(
                $intervalInSeconds * 1000,
                function (): void {
                    $this->line('Saving statistics...');
                    StatisticsCollectorFacade::save();
                }
            );
        }
    }

    /**
     * Configure the restart timer.
     *
     * @return void
     */
    public function configureRestartTimer(): void
    {
        $this->lastRestart = $this->getLastRestart();

        $this->loop->repeat(
            10000,
            function () {
                if ($this->getLastRestart() !== $this->lastRestart) {
                    $this->triggerSoftShutdown();
                }
            }
        );
    }

    /**
     * Register the routes for the server.
     *
     * @return void
     */
    protected function configureRoutes(): void
    {
        WebSocketRouter::registerRoutes();
    }

    /**
     * Configure the PCNTL signals for soft shutdown.
     *
     * @return void
     */
    protected function configurePcntlSignal(): void
    {
        // When the process receives a SIGTERM or a SIGINT signal, it should
        // mark the server as unavailable to receive new connections, close
        // the current connections, and stopping the websocket server loop.

        if (!extension_loaded('pcntl')) {
            return;
        }

        $this->loop->onSignal(SIGTERM, [$this, 'triggerSoftShutdown']);
        $this->loop->onSignal(SIGINT, [$this, 'triggerSoftShutdown']);
    }

    /**
     * Configure the tracker that will delete
     * from the store the connections that.
     *
     * @return void
     */
    protected function configurePongTracker(): void
    {
        $this->loop->repeat(
            10000,
            function () {
                $this->laravel
                    ->make(ChannelManager::class)
                    ->removeObsoleteConnections();
            }
        );
    }

    /**
     * Configure the HTTP logger class.
     *
     * @return void
     */
    protected function configureHttpLogger(): void
    {
        $this->laravel->singleton(
            HttpLogger::class,
            function ($app) {
                return (new HttpLogger($this->output))
                    ->enable($this->option('debug') ?: ($app['config']['app']['debug'] ?? false))
                    ->verbose($this->output->isVerbose());
            }
        );
    }

    /**
     * Configure the logger for messages.
     *
     * @return void
     */
    protected function configureMessageLogger(): void
    {
        $this->laravel->singleton(
            WebSocketsLogger::class,
            function ($app) {
                return (new WebSocketsLogger($this->output))
                    ->enable($this->option('debug') ?: ($app['config']['app']['debug'] ?? false))
                    ->verbose($this->output->isVerbose());
            }
        );
    }

    /**
     * Configure the connection logger.
     *
     * @return void
     */
    protected function configureConnectionLogger(): void
    {
        $this->laravel->bind(
            ConnectionLogger::class,
            function ($app) {
                return (new ConnectionLogger($this->output))
                    ->enable($app['config']['app']['debug'] ?? false)
                    ->verbose($this->output->isVerbose());
            }
        );
    }

    /**
     * Start the server.
     *
     * @return void
     */
    protected function startServer(): void
    {
        $this->line('Starting the WebSocket server.');

        $this->info('Listening on addresses:');

        foreach ($listeners = explode(',', $this->option('listen')) as $listen) {
            $this->info(" - $listen");
        }

        $this->newLine();

        $this->server = $this->buildServerInterface($listeners);

        $this->isShuttingDown = false;

        $this->server->start();

        $this->loop->start();

        $this->isShuttingDown = false;

        // Avoid dangling routines by stopping the loop.
        $this->loop->stop();
    }

    /**
     * Builds a Server.
     *
     * @param  array  $listeners
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function buildServerInterface(array $listeners): Server
    {
        /** @var \BeyondCode\LaravelWebSockets\Servers\ServerManager $manager */
        $manager = $this->laravel->make(ServerManager::class);

        return $manager->driver($this->option('server') ?: null)
            ->listenOn(...$listeners)
            ->logTo($this->laravel->make('log'))
            ->outputTo($this->output);
    }

    /**
     * Build the server instance.
     *
     * @param  array  $listeners
     *
     * @return \Amp\Http\Server\HttpServer
     * @throws \Amp\Socket\SocketException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function buildServer(array $listeners): HttpServer
    {
        return (new ServerFactory($this->laravel->make('log')))
            ->withRoutes(WebSocketRouter::getRoutes())
            ->setConsoleOutput($this->output)
            ->listenOn($listeners)
            ->createServer();
    }

    /**
     * Get the last time the server restarted.
     *
     * @return int
     */
    protected function getLastRestart(): int
    {
        return Cache::get('beyondcode:websockets:restart', 0);
    }

    /**
     * Trigger a soft shutdown for the process.
     *
     * Subsequent calls to this method are idempotent.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function triggerSoftShutdown(): void
    {
        if ($this->isShuttingDown) {
            return;
        }

        $this->line('Closing existing connections...');

        /** @var \BeyondCode\LaravelWebSockets\Contracts\ChannelManager $channelManager */
        $channelManager = $this->laravel->make(ChannelManager::class);

        $this->info('Shutting down Websocket Servers...');

        // Close the new clients allowance on this server.
        $channelManager->declineNewConnections();

        // Get all local clients and close them. They will
        // be automatically be unsubscribed from all channels.
        $channelManager->getLocalClients()->then(
            function ($clients): void {
                static::closeAllConnections($clients);
            }
        );

        $this->info('Exiting...');

        // Stop all functions.
        $this->loop->stop();
    }

    /**
     * Close the connection of each Client connected to the server.
     *
     * @param  array|\Amp\Websocket\Client[]  $clients
     */
    public function closeAllConnections(array $clients): void
    {
        foreach ($clients as $client) {
            $client->close();
        }

        $this->info('Closed ' . count($clients) . ' connections.');
    }
}
