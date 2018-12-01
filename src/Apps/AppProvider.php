<?php

namespace BeyondCode\LaravelWebSockets\Apps;

interface AppProvider
{
    /**  @return array[BeyondCode\LaravelWebSockets\AppProviders\App] */
    public function all(): array;

    public function findById(int $appId): ?App;

    public function findByKey(string $appKey): ?App;
}