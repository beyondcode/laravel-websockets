<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

use Exception;

class InvalidApp extends Exception
{
    public static function notFound($appId)
    {
        return new static("Could not find app for app id `{$appId}`.");
    }

    public static function valueIsRequired($name, $appId)
    {
        return new static("{$name} is required but was empty for app id `{$appId}`.");
    }
}
