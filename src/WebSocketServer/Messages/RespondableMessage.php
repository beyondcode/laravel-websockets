<?php
namespace BeyondCode\LaravelWebSockets\WebSocketServer\Messages;

interface RespondableMessage
{
    public function respond();
}