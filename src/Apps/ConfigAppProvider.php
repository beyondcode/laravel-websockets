<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use Illuminate\Support\Collection;

class ConfigAppProvider implements AppProvider
{
    /** @var Collection */
    protected $apps;

    public function __construct()
    {
        $this->apps = collect(config('websockets.clients'));
    }

    /**  @return array[\BeyondCode\LaravelWebSockets\ClientProviders\Client] */
    public function all(): array
    {
        return $this->apps
            ->map(function (array $appAttributes) {
                return $this->instanciate($appAttributes);
            })
            ->toArray();
    }

    public function findById(int $appId): ?App
    {
        $appAttributes = $this
            ->apps
            ->firstWhere('id', $appId);

        return $this->instanciate($appAttributes);
    }

    public function findByKey(string $appKey): ?App
    {
        $appAttributes = $this
            ->apps
            ->firstWhere('key', $appKey);

        return $this->instanciate($appAttributes);
    }

    protected function instanciate(?array $appAttributes): ?App
    {
        if (! $appAttributes) {
            return null;
        }

        return new App(
            $appAttributes['id'],
            $appAttributes['key'],
            $appAttributes['secret'],
            $appAttributes['name'] ?? null
        );
    }
}