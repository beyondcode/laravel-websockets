<?php

namespace BeyondCode\LaravelWebSockets\Database;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Apps\AppProvider as IAppProvider;
use BeyondCode\LaravelWebSockets\Database\Models\App as DatabaseApp;

class AppProvider implements IAppProvider
{
    public function all(): array
    {
        return DatabaseApp::all()->map(function (DatabaseApp $app) {
            return $this->instanciate($app->toArray());
        })->toArray();
    }

    public function findById($appId): ?App
    {
        return $this->instanciate(DatabaseApp::find($appId)->toArray() ?? null);
    }

    public function findByKey(string $appKey): ?App
    {
        return $this->instanciate(DatabaseApp::where('key', $appKey)->first()->toArray() ?? null);
    }

    public function findBySecret(string $appSecret): ?App
    {
        return $this->instanciate(DatabaseApp::where('secret', $appSecret)->first()->toArray() ?? null);
    }

    protected function instanciate(?array $appAttributes): ?App
    {
        if (! $appAttributes) {
            return null;
        }

        $app = new App($appAttributes['id'], $appAttributes['key'], $appAttributes['secret']);

        if (isset($appAttributes['name'])) {
            $app->setName($appAttributes['name']);
        }

        if (isset($appAttributes['host'])) {
            $app->setHost($appAttributes['host']);
        }

        $app->enableClientMessages($appAttributes['enable_client_messages'])
            ->enableStatistics($appAttributes['enable_statistics']);

        return $app;
    }
}
