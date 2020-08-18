<?php

namespace BeyondCode\LaravelWebSockets\Tests\ClientProviders;

use BeyondCode\LaravelWebSockets\Apps\ConfigAppManager;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class ConfigAppManagerTest extends TestCase
{
    /** @var \BeyondCode\LaravelWebSockets\Apps\ConfigAppManager */
    protected $appManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->appManager = new ConfigAppManager;
    }

    /** @test */
    public function it_can_get_apps_from_the_config_file()
    {
        $apps = $this->appManager->all();

        $this->assertCount(2, $apps);

        /** @var $app */
        $app = $apps[0];

        $this->assertEquals('Test App', $app->name);
        $this->assertEquals(1234, $app->id);
        $this->assertEquals('TestKey', $app->key);
        $this->assertEquals('TestSecret', $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }

    /** @test */
    public function it_can_find_app_by_id()
    {
        $app = $this->appManager->findById(0000);

        $this->assertNull($app);

        $app = $this->appManager->findById(1234);

        $this->assertEquals('Test App', $app->name);
        $this->assertEquals(1234, $app->id);
        $this->assertEquals('TestKey', $app->key);
        $this->assertEquals('TestSecret', $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }

    /** @test */
    public function it_can_find_app_by_key()
    {
        $app = $this->appManager->findByKey('InvalidKey');

        $this->assertNull($app);

        $app = $this->appManager->findByKey('TestKey');

        $this->assertEquals('Test App', $app->name);
        $this->assertEquals(1234, $app->id);
        $this->assertEquals('TestKey', $app->key);
        $this->assertEquals('TestSecret', $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }

    /** @test */
    public function it_can_find_app_by_secret()
    {
        $app = $this->appManager->findBySecret('InvalidSecret');

        $this->assertNull($app);

        $app = $this->appManager->findBySecret('TestSecret');

        $this->assertEquals('Test App', $app->name);
        $this->assertEquals(1234, $app->id);
        $this->assertEquals('TestKey', $app->key);
        $this->assertEquals('TestSecret', $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }
}
