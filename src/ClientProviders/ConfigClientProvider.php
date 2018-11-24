<?php

namespace BeyondCode\LaravelWebSockets\ClientProviders;

class ConfigClientProvider implements ClientProvider
{
    public function getClients(): array
    {
        return config('laravel-websockets.clients');
    }

    public function findClient(string $appKey): ?Client
    {
        $allClients = collect(config('websockets.clients'));

        $client = $allClients->firstWhere('app_key', $appKey);

        if (! $client) {
            return null;
        }

        return new Client(
            $client['app_id'],
            $client['app_key'],
            $client['app_secret']
        );
    }
}