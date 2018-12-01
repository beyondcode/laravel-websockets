<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use Illuminate\Support\Collection;

class ConfigAppProvider implements AppProvider
{
    /** @var Collection */
    protected $clients;

    public function __construct()
    {
        $this->clients = collect(config('websockets.clients'));
    }

    /**  @return array[\BeyondCode\LaravelWebSockets\ClientProviders\Client] */
    public function all(): array
    {
        return $this->clients
            ->map(function ($client) {
                return $this->instanciate($client);
            })
            ->toArray();
    }

    public function findById(int $appId): ?App
    {
        $clientAttributes = $this
            ->clients
            ->firstWhere('id', $appId);

        return $this->instanciate($clientAttributes);
    }

    public function findByKey(string $appKey): ?App
    {
        $clientAttributes = $this
            ->clients
            ->firstWhere('key', $appKey);

        return $this->instanciate($clientAttributes);
    }

    protected function instanciate(?array $clientAttributes): ?App
    {
        if (! $clientAttributes) {
            return null;
        }

        return new App(
            $clientAttributes['id'],
            $clientAttributes['key'],
            $clientAttributes['secret'],
            $clientAttributes['name'] ?? null
        );
    }
}