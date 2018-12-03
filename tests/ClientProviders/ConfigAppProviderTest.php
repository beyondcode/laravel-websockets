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
    public function it_can_get_apps_from_the_config_file()
    {
        $apps = $this->configAppProvider->all();

        $this->assertCount(1, $apps);

        /** @var  $app */
        $app = $apps[0];

        $this->assertEquals('Test App', $app->name);
        $this->assertEquals(1234, $app->id);
        $this->assertEquals('TestKey', $app->key);
        $this->assertEquals('TestSecret', $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }
}