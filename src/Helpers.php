<?php

namespace BeyondCode\LaravelWebSockets;

use Amp\Promise;
use Amp\Success;

class Helpers
{
    /**
     * Transform the Redis' list of key after value
     * to key-value pairs.
     *
     * @param  array  $list
     * @return array
     */
    public static function redisListToArray(array $list): array
    {
        // Redis lists come into a format where the keys are on even indexes
        // and the values are on odd indexes. This way, we know which
        // ones are keys and which ones are values and their get combined
        // later to form the key => value array.
        [$keys, $values] = collect($list)->partition(function ($value, $key) {
            return $key % 2 === 0;
        });

        return array_combine($keys->all(), $values->all());
    }

    /**
     * Create a new fulfilled promise with a value.
     *
     * @param  mixed  $value
     *
     * @return \Amp\Promise
     */
    public static function createFulfilledPromise($value): Promise
    {
        return new (config('websockets.promise_resolver', Success::class))($value);
    }
}
