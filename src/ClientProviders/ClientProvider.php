<?php

namespace BeyondCode\LaravelWebSockets\ClientProviders;


interface ClientProvider
{
    public function findByAppId(int $appId): ?Client;

    public function findByAppKey(string $appKey): ?Client;
}