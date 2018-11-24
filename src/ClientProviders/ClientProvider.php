<?php

namespace BeyondCode\LaravelWebSockets\ClientProviders;


interface ClientProvider
{
    public function findClient(string $appId): ?Client;
}