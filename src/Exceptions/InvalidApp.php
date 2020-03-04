<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

use Exception;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;

class InvalidApp extends Exception implements ProvidesSolution
{
    public static function notFound($appId)
    {
        return new static("Could not find app for app id `{$appId}`.");
    }

    public static function valueIsRequired($name, $appId)
    {
        return new static("{$name} is required but was empty for app id `{$appId}`.");
    }

    public function getSolution(): Solution
    {
        return BaseSolution::create('Your application id could not be found')
            ->setSolutionDescription('Make sure that your `config/websockets.php` contains the app key you are trying to use.')
            ->setDocumentationLinks([
                'Configuring WebSocket Apps (official documentation)' => 'https://docs.beyondco.de/laravel-websockets/1.0/basic-usage/pusher.html#configuring-websocket-apps',
            ]);
    }
}
