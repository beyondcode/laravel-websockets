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

    /** @var bool */
    public $clientMessagesEnabled = false;

    public static function findById($appId)
    {
        return app(AppProvider::class)->findById($appId);
    }

    public static function findByKey(string $appKey): ?App
    {
        return app(AppProvider::class)->findByKey($appKey);
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

    public function enableClientMessages(bool $enabled = true)
    {
        $this->clientMessagesEnabled = $enabled;

        return $this;
    }
}
