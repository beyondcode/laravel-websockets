<?php

namespace BeyondCode\LaravelWebSockets\ClientProviders;

use Illuminate\Support\Collection;

class ConfigClientProvider implements ClientProvider
{
    public function findByAppId(int $appId): ?Client
    {
        $clientAttributes = $this
            ->allClients()
            ->firstWhere('app_id', $appId);

        return $this->instanciate($clientAttributes);
    }

    public function findByAppKey(string $appKey): ?Client
    {
        $clientAttributes = $this
            ->allClients()
            ->firstWhere('app_key', $appKey);

        return $this->instanciate($clientAttributes);
    }

    public function all(): array
    {
        return $this->allClients()
            ->map(function ($client) {
                return $this->instanciate($client);
            })
            ->toArray();
    }

    protected function allClients(): Collection
    {
        return collect(config('websockets.clients'));
    }

    protected function instanciate(?array $clientAttributes): ?Client
    {
        if (! $clientAttributes) {
            return null;
        }

        return new Client(
            $clientAttributes['app_id'],
            $clientAttributes['app_key'],
            $clientAttributes['app_secret'],
            $clientAttributes['name'] ?? null
        );
    }
}