<?php

namespace BeyondCode\LaravelWebSockets\ClientProviders;

use Illuminate\Support\Collection;

class ConfigClientProvider implements ClientProvider
{
    /** @var Collection */
    protected $clients;

    public function __construct()
    {
        $this->clients = collect(config('websockets.clients'));
    }

    /**  @return array[BeyondCode\LaravelWebSockets\ClientProviders\Client] */
    public function all(): array
    {
        return $this->clients
            ->map(function ($client) {
                return $this->instanciate($client);
            })
            ->toArray();
    }
    public function findByAppId(int $appId): ?Client
    {
        $clientAttributes = $this
            ->clients
            ->firstWhere('app_id', $appId);

        return $this->instanciate($clientAttributes);
    }

    public function findByAppKey(string $appKey): ?Client
    {
        $clientAttributes = $this
            ->clients
            ->firstWhere('app_key', $appKey);

        return $this->instanciate($clientAttributes);
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