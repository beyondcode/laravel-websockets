<?php

namespace BeyondCode\LaravelWebSockets\ClientProviders;


interface ClientProvider
{
    /**  @return array[BeyondCode\LaravelWebSockets\ClientProviders\Client] */
    public function all(): array;

    public function findByAppId(int $appId): ?Client;

    public function findByAppKey(string $appKey): ?Client;
}