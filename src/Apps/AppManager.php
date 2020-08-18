<?php

namespace BeyondCode\LaravelWebSockets\Apps;

interface AppManager
{
    /**  @return array[BeyondCode\LaravelWebSockets\Apps\App] */
    public function all(): array;

    public function findById($appId): ?App;

    public function findByKey(string $appKey): ?App;

    public function findBySecret(string $appSecret): ?App;
}
