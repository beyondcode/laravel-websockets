<?php

namespace BeyondCode\LaravelWebSockets\Servers;

use Amp\Websocket\Server\Websocket;
use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use BeyondCode\LaravelWebSockets\Contracts\Loop;
use BeyondCode\LaravelWebSockets\Contracts\Server;
use BeyondCode\LaravelWebSockets\Routing\Router;
use BeyondCode\LaravelWebSockets\Servers\Drivers\AmpClientHandler;
use BeyondCode\LaravelWebSockets\Servers\Drivers\AmpServer;
use BeyondCode\LaravelWebSockets\WebSocketHandler;
use Illuminate\Support\Manager;


/**
 * @method \BeyondCode\LaravelWebSockets\Contracts\Server driver(string $driver = null)
 *
 * @method \BeyondCode\LaravelWebSockets\Contracts\Server listenOn(string ...$address)
 * @method \BeyondCode\LaravelWebSockets\Contracts\Server outputTo(\Symfony\Component\Console\Output\OutputInterface $output)
 * @method \BeyondCode\LaravelWebSockets\Contracts\Server logTo(\Psr\Log\LoggerInterface $logger)
 * @method \BeyondCode\LaravelWebSockets\Contracts\Server routeUsing(\BeyondCode\LaravelWebSockets\Routing\Router $router)
 * @method void start(callable $callable = null)
 * @method void stop(callable $callable = null)
 */
class ServerManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return 'amp';
    }

    /**
     * Creates a new Amp Websocket Server.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    protected function createAmpDriver(): Server
    {
        return new AmpServer(
            new Websocket(
//                new AmpClientHandler(
//                    $this->container->make(Router::class),
//                    $this->container->make(AppManager::class)
//                )
            new WebSocketHandler()
            ),
            $this->container->make(Loop::class)
        );
    }
}
