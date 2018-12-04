<?php


namespace BeyondCode\LaravelWebSockets\Statistics;

use React\Dns\Resolver\Resolver;
use React\Promise\FulfilledPromise;

class DnsResolver extends Resolver
{
    public function resolve($domain)
    {
        return new FulfilledPromise('127.0.0.1');
    }
}