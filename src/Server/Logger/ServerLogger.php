<?php declare(strict_types=1);

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Exception;
use React\Socket\ServerInterface;

class ServerLogger extends Logger
{
    public static function registerListeners(ServerInterface $server): void
    {
        /** @var ServerLogger $logger */
        $logger = app(self::class);

        $server->on('error', function (Exception $e) use ($logger) {
            $logger->error($e->getMessage());
        });
    }
}
