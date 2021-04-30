<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;

class ConfigAppManager implements AppManager
{
    /**
     * The list of apps.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $apps;

    /**
     * Initialize the class.
     *
     * @return void
     */
    public function __construct()
    {
        $this->apps = collect(config('websockets.apps'));
    }

    /**
     * Get all apps.
     *
     * @return array[\BeyondCode\LaravelWebSockets\Apps\App]
     */
    public function all(): array
    {
        return $this->apps
            ->map(function (array $appAttributes) {
                return $this->convertIntoApp($appAttributes);
            })
            ->toArray();
    }

    /**
     * Get app by id.
     *
     * @param  string|int  $appId
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public function findById($appId): ?App
    {
        return $this->convertIntoApp(
            $this->apps->firstWhere('id', $appId)
        );
    }

    /**
     * Get app by app key.
     *
     * @param  string  $appKey
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public function findByKey($appKey): ?App
    {
        return $this->convertIntoApp(
            $this->apps->firstWhere('key', $appKey)
        );
    }

    /**
     * Get app by secret.
     *
     * @param  string  $appSecret
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public function findBySecret($appSecret): ?App
    {
        return $this->convertIntoApp(
            $this->apps->firstWhere('secret', $appSecret)
        );
    }

    /**
     * Map the app into an App instance.
     *
     * @param  array|null  $app
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    protected function convertIntoApp(?array $appAttributes): ?App
    {
        if (! $appAttributes) {
            return null;
        }

        $app = new App(
            $appAttributes['id'],
            $appAttributes['key'],
            $appAttributes['secret']
        );

        if (isset($appAttributes['name'])) {
            $app->setName($appAttributes['name']);
        }

        if (isset($appAttributes['host'])) {
            $app->setHost($appAttributes['host']);
        }

        if (isset($appAttributes['path'])) {
            $app->setPath($appAttributes['path']);
        }

        $app
            ->enableClientMessages($appAttributes['enable_client_messages'])
            ->enableStatistics($appAttributes['enable_statistics'])
            ->setCapacity($appAttributes['capacity'] ?? null)
            ->setAllowedOrigins($appAttributes['allowed_origins'] ?? []);

        return $app;
    }
}
