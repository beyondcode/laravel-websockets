<?php

namespace BeyondCode\LaravelWebsockets\LaravelEcho\Pusher\Exceptions;

use Exception;

class PusherException extends Exception
{
    public function getPayload()
    {
        return [
            'event' => 'pusher:error',
            'data' => [
                'message' => $this->getMessage(),
                'code' => $this->getCode()
            ]
        ];
    }
}