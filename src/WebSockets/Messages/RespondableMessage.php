<?php
namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

interface RespondableMessage
{
    public function respond();
}