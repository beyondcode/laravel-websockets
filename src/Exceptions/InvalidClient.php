<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;


use Exception;

class InvalidClient extends Exception
{
    public static function notFound(int $appId)
    {
        return new static("Could not find client for app id `{$appId}`.");
    }

    public static function appIdIsNotNumeric($appId)
    {
        return new static("Invalid app id `{$appId}` found. An app id should be numeric.");
    }

    public static function valueIsRequired($name, int $appId)
    {
        return new static("{$name} is required but was empty for app id {$appId}");
    }
}