<?php

namespace BeyondCode\LaravelWebSockets\Statistics;

use React\Dns\Resolver\ResolverInterface;
use React\Promise\FulfilledPromise;

class DnsResolver implements ResolverInterface
{
    private $internalIP = '127.0.0.1';

    /*
     * This empty constructor is needed so we don't have to setup the parent's dependencies.
     */
    public function __construct()
    {
        //
    }

    public function resolve($domain)
    {
        return new FulfilledPromise($this->internalIP);
    }

    public function resolveAll($domain, $type)
    {
        return new FulfilledPromise([$this->internalIP]);
    }

    public function __toString()
    {
        return $this->internalIP;
    }
}
