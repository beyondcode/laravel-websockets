<?php

namespace BeyondCode\LaravelWebSockets\Statistics;

use React\Dns\Resolver\Resolver;
use React\Promise\FulfilledPromise;

class DnsResolver extends Resolver
{
    /*
     * This empty constructor is needed so we don't have to setup the parent's dependencies.
     */
    public function __construct()
    {
        //
    }

    public function resolve($domain)
    {
        return new FulfilledPromise('127.0.0.1');
    }
}