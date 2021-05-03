<?php

namespace BeyondCode\LaravelWebSockets\Console\Commands;

use Amp\Http\Server\HttpServer;
use Amp\Loop;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Facades\StatisticsCollector as StatisticsCollectorFacade;
use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;
use BeyondCode\LaravelWebSockets\Server\Loggers\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Loggers\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Loggers\WebSocketsLogger;
use BeyondCode\LaravelWebSockets\ServerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

use function Amp\Promise\wait;

class StartServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:serve
        {--listen=0.0.0.0:6001,[::1]:6001 : Address to listen to, separated by comma (0.0.0.0:6001,[::1]:6001)}
        {--disable-statistics : Disable the statistics tracking.}
        {--statistics-interval= : The amount of seconds to tick between statistics saving.}
        {--debug : Forces the loggers to be enabled and thereby overriding the APP_DEBUG setting.}
    ';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Start the LaravelWebSockets server.';

    /**
     * The Pusher server instance.
     *
     * @var \Amp\Http\Server\HttpServer
     */
    public $server;

    /**
     * Last time the server restarted.
     *
     * @var int
     */
    protected $lastRestart;

    /**
     * Run the command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->configureLoggers();

        $this->configureManagers();

        $this->configureStatistics();

        $this->configureRestartTimer();

        $this->configureRoutes();

        $this->configurePcntlSignal();

        $this->configurePongTracker();

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

            Loop::repeat(
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
    public function configureRestartTimer()
    {
        $this->lastRestart = $this->getLastRestart();

        Loop::repeat(
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
    protected function configureRoutes()
    {
        WebSocketRouter::registerRoutes();
    }

    /**
     * Configure the PCNTL signals for soft shutdown.
     *
     * @return void
     */
    protected function configurePcntlSignal()
    {
        // When the process receives a SIGTERM or a SIGINT
        // signal, it should mark the server as unavailable
        // to receive new connections, close the current connections,
        // then stopping the loop.

        if (!extension_loaded('pcntl')) {
            return;
        }

        Loop::onSignal(
            SIGTERM,
            function () {
                $this->line('Closing existing connections...');

                $this->triggerSoftShutdown();
            }
        );

        Loop::onSignal(
            SIGINT,
            function () {
                $this->line('Closing existing connections...');

                $this->triggerSoftShutdown();
            }
        );
    }

    /**
     * Configure the tracker that will delete
     * from the store the connections that.
     *
     * @return void
     */
    protected function configurePongTracker()
    {
        Loop::repeat(
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
    protected function configureHttpLogger()
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
    protected function configureMessageLogger()
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
    protected function configureConnectionLogger()
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
        $this->info("Starting the WebSocket server on addresses:");
        foreach ($listeners = explode(',', $this->option('listen')) as $listen) {
            $this->info(" - $listen");
        }
        $this->info('');

        $this->server = $this->buildServer($listeners);

        $this->server->start();

        Loop::run();
    }

    /**
     * Build the server instance.
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
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function triggerSoftShutdown(): void
    {
        /** @var \BeyondCode\LaravelWebSockets\Contracts\ChannelManager $channelManager */
        $channelManager = $this->laravel->make(ChannelManager::class);

        $this->info('Shutting down Websocket Servers...');

        // Close the new clients allowance on this server.
        $channelManager->declineNewConnections();

        // Get all local clients and close them. They will
        // be automatically be unsubscribed from all channels.
        $channelManager->getLocalConnections()->onResolve(
            function ($error, $clients): void {
                static::closeAllConnections($clients);
            }
        );

        $this->info('Done.');

        Loop::stop();
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
