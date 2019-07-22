<?php

namespace BeyondCode\LaravelWebSockets\Statistics;

use React\Promise\FulfilledPromise;
use React\Dns\Resolver\ResolverInterface;

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
        return $this->resolveInternal($domain);
    }

    public function resolveAll($domain, $type)
    {
        return $this->resolveInternal($domain, $type);
    }

    private function resolveInternal($domain, $type = null)
    {
        return new FulfilledPromise($this->internalIP);
    }

    public function __toString()
    {
        return $this->internalIP;
    }
}
