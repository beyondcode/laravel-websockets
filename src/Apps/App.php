<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;

class App
{
    /** @var string|int */
    public $id;

    /** @var string */
    public $key;

    /** @var string */
    public $secret;

    /** @var string|null */
    public $name;

    /** @var string|null */
    public $host;

    /** @var string|null */
    public $path;

    /** @var int|null */
    public $capacity = null;

    /** @var bool */
    public $clientMessagesEnabled = false;

    /** @var bool */
    public $statisticsEnabled = true;

    /** @var array */
    public $allowedOrigins = [];

    /** @var array|\Amp\Websocket\Client[]  */
    public $clients = [];

    /**
     * Find the app by id.
     *
     * @param  string|int  $appId
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public static function findById($appId): ?App
    {
        return app(AppManager::class)->findById($appId);
    }

    /**
     * Find the app by app key.
     *
     * @param  string  $appKey
     *
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public static function findByKey(string $appKey): ?App
    {
        return app(AppManager::class)->findByKey($appKey);
    }

    /**
     * Find the app by app secret.
     *
     * @param  string  $appSecret
     *
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public static function findBySecret(string $appSecret): ?App
    {
        return app(AppManager::class)->findBySecret($appSecret);
    }

    /**
     * Initialize the Web Socket app instance.
     *
     * @param  string|int  $appId
     * @param  string  $appKey
     * @param  string  $appSecret
     */
    public function __construct($appId, string $appKey, string $appSecret)
    {
        $this->id = $appId;
        $this->key = $appKey;
        $this->secret = $appSecret;
    }

    /**
     * Set the name of the app.
     *
     * @param  string  $appName
     * @return $this
     */
    public function setName(string $appName): App
    {
        $this->name = $appName;

        return $this;
    }

    /**
     * Set the app host.
     *
     * @param  string  $host
     * @return $this
     */
    public function setHost(string $host): App
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set path for the app.
     *
     * @param  string  $path
     * @return $this
     */
    public function setPath(string $path): App
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Enable client messages.
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function enableClientMessages(bool $enabled = true): App
    {
        $this->clientMessagesEnabled = $enabled;

        return $this;
    }

    /**
     * Set the maximum capacity for the app.
     *
     * @param  int|null  $capacity
     * @return $this
     */
    public function setCapacity(?int $capacity): App
    {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * Enable statistics for the app.
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function enableStatistics(bool $enabled = true): App
    {
        $this->statisticsEnabled = $enabled;

        return $this;
    }

    /**
     * Add whitelisted origins.
     *
     * @param  array  $allowedOrigins
     * @return $this
     */
    public function setAllowedOrigins(array $allowedOrigins): App
    {
        $this->allowedOrigins = $allowedOrigins;

        return $this;
    }
}
