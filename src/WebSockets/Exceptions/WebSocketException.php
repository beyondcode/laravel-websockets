<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

use Exception;

class WebSocketException extends Exception
{
    /**
     * Get the payload, Pusher-like formatted.
     *
     * @return array
     */
    public function getPayload()
    {
        return [
            'event' => 'pusher:error',
            'data' => [
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
            ],
        ];
    }
}
