<?php

namespace BeyondCode\LaravelWebSockets\Tests\ClientProviders;

use BeyondCode\LaravelWebSockets\ClientProviders\ConfigClientProvider;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class ConfigClientProviderTest extends TestCase
{
    /** @var \BeyondCode\LaravelWebSockets\ClientProviders\ConfigClientProvider */
    protected $configClientProvider;

    public function setUp()
    {
        parent::setUp();

        $this->configClientProvider = new ConfigClientProvider();
    }

    /** @test */
    public function it_can_get_client_from_the_config_file()
    {
        $clients = $this->configClientProvider->all();

        $this->assertCount(1, $clients);

        /** @var  $client */
        $client = $clients[0];

        $this->assertEquals('Test Client', $client->name);
        $this->assertEquals(1234, $client->appId);
        $this->assertEquals('TestKey', $client->appKey);
        $this->assertEquals('TestSecret', $client->appSecret);


    }
}