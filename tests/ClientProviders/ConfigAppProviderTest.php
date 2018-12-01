<?php

namespace BeyondCode\LaravelWebSockets\Tests\ClientProviders;

use BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class ConfigAppProviderTest extends TestCase
{
    /** @var \BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider */
    protected $configAppProvider;

    public function setUp()
    {
        parent::setUp();

        $this->configAppProvider = new ConfigAppProvider();
    }

    /** @test */
    public function it_can_get_client_from_the_config_file()
    {
        $apps = $this->configAppProvider->all();

        $this->assertCount(1, $apps);

        /** @var  $client */
        $client = $apps[0];

        $this->assertEquals('Test App', $client->name);
        $this->assertEquals(1234, $client->id);
        $this->assertEquals('TestKey', $client->key);
        $this->assertEquals('TestSecret', $client->secret);


    }
}