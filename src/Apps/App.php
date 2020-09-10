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

    /**
     * Find the app by id.
     *
     * @param  string|int  $appId
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public static function findById($appId)
    {
        return app(AppManager::class)->findById($appId);
    }

    /**
     * Find the app by app key.
     *
     * @param  string  $appKey
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public static function findByKey($appKey): ?self
    {
        return app(AppManager::class)->findByKey($appKey);
    }

    /**
     * Find the app by app secret.
     *
     * @param  string  $appSecret
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    public static function findBySecret($appSecret): ?self
    {
        return app(AppManager::class)->findBySecret($appSecret);
    }

    /**
     * Initialize the Web Socket app instance.
     *
     * @param  string|int  $appId
     * @param  string  $key
     * @param  string  $secret
     * @return void
     */
    public function __construct($appId, $appKey, $appSecret)
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
    public function setName(string $appName)
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
    public function setHost(string $host)
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
    public function setPath(string $path)
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
    public function enableClientMessages(bool $enabled = true)
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
    public function setCapacity(?int $capacity)
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
    public function enableStatistics(bool $enabled = true)
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
    public function setAllowedOrigins(array $allowedOrigins)
    {
        $this->allowedOrigins = $allowedOrigins;

        return $this;
    }
}
