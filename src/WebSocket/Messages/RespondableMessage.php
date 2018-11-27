<?php
namespace BeyondCode\LaravelWebSockets\WebSocket\Messages;

interface RespondableMessage
{
    public function respond();
}