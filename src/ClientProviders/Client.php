<?php

namespace BeyondCode\LaravelWebSockets\ClientProviders;


use BeyondCode\LaravelWebSockets\Exceptions\InvalidClient;

class Client
{
    /** @var int */
    public $appId;

    /** @var string */
    public $appKey;

    /** @var string */
    public $appSecret;

    public static function findByAppId(int $appId)
    {
        return app(ClientProvider::class)->findByAppId($appId);
    }

    public static function findByAppKey(string $appKey): ?Client
    {
        return app(ClientProvider::class)->findByAppKey($appKey);
    }

    public function __construct($appId, string $appKey, string $appSecret)
    {
        if (!is_numeric($appId)) {
            throw InvalidClient::appIdIsNotNumeric($appId);
        }

        if ($appKey === '') {
            throw InvalidClient::valueIsRequired('appKey', $appId);
        }

        if ($appSecret === '') {
            throw InvalidClient::valueIsRequired('appSecret', $appId);
        }

        $this->appId = $appId;

        $this->appKey = $appKey;

        $this->appSecret = $appSecret;
    }




}