<?php

namespace BeyondCode\LaravelWebSockets\WebSocketServer\Pusher\Exceptions;

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