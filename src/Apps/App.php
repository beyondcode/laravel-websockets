<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use BeyondCode\LaravelWebSockets\Exceptions\InvalidApp;

class App
{
    /** @var int */
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

    public static function findById($appId)
    {
        return app(AppProvider::class)->findById($appId);
    }

    public static function findByKey(string $appKey): ?self
    {
        return app(AppProvider::class)->findByKey($appKey);
    }

    public static function findBySecret(string $appSecret): ?self
    {
        return app(AppProvider::class)->findBySecret($appSecret);
    }

    public function __construct($appId, string $appKey, string $appSecret)
    {
        if ($appKey === '') {
            throw InvalidApp::valueIsRequired('appKey', $appId);
        }

        if ($appSecret === '') {
            throw InvalidApp::valueIsRequired('appSecret', $appId);
        }

        $this->id = $appId;

        $this->key = $appKey;

        $this->secret = $appSecret;
    }

    public function setName(string $appName)
    {
        $this->name = $appName;

        return $this;
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    public function enableClientMessages(bool $enabled = true)
    {
        $this->clientMessagesEnabled = $enabled;

        return $this;
    }

    public function setCapacity(?int $capacity)
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function enableStatistics(bool $enabled = true)
    {
        $this->statisticsEnabled = $enabled;

        return $this;
    }
}
