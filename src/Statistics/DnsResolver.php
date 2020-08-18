<?php

namespace BeyondCode\LaravelWebSockets\Statistics;

use React\Dns\Resolver\ResolverInterface;
use React\Promise\FulfilledPromise;

class DnsResolver implements ResolverInterface
{
    /**
     * The internal IP to use.
     *
     * @var string
     */
    private $internalIp = '127.0.0.1';

    /**
     * Resolve the DNSes.
     *
     * @param  string  $domain
     * @return \React\Promise\PromiseInterface
     */
    public function resolve($domain)
    {
        return $this->resolveInternal($domain);
    }

    /**
     * Resolve all domains.
     *
     * @param  string  $domain
     * @param  string  $type
     * @return FulfilledPromise
     */
    public function resolveAll($domain, $type)
    {
        return $this->resolveInternal($domain, $type);
    }

    /**
     * Resolve the internal domain.
     *
     * @param  string  $domain
     * @param  string  $type
     * @return FulfilledPromise
     */
    private function resolveInternal($domain, $type = null)
    {
        return new FulfilledPromise($this->internalIp);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->internalIp;
    }
}
