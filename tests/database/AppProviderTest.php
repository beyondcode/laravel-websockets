<?php

namespace BeyondCode\LaravelWebSockets\Tests\Database;

use BeyondCode\LaravelWebSockets\Database\AppProvider;
use BeyondCode\LaravelWebSockets\Database\Models\App;
use BeyondCode\LaravelWebSockets\Test\TestCase;
use Illuminate\Support\Str;

class AppProviderTest extends TestCase
{
    /** @var AppProvider */
    protected $databaseAppProvider;

    /** @var App */
    private $databaseApp;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseAppProvider = new AppProvider();

        $this->databaseApp = App::create([
            'name' => 'Application One',
            'host' => 'example-1.test',
            'key' => Str::random(),
            'secret' => Str::random(32),
            'enable_client_messages' => false,
            'enable_statistics' => true,
        ]);
    }

    /** @test */
    public function it_can_get_apps_from_the_database()
    {
        $apps = $this->databaseAppProvider->all();

        $this->assertCount(1, $apps);

        /** @var $app */
        $app = $apps[0];

        $this->assertEquals($this->databaseApp->name, $app->name);
        $this->assertEquals($this->databaseApp->id, $app->id);
        $this->assertEquals($this->databaseApp->key, $app->key);
        $this->assertEquals($this->databaseApp->secret, $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }

    /** @test */
    public function it_can_find_app_by_id()
    {
        $app = $this->databaseAppProvider->findById(0000);

        $this->assertNull($app);

        $app = $this->databaseAppProvider->findById($this->databaseApp->id);

        $this->assertEquals($this->databaseApp->name, $app->name);
        $this->assertEquals($this->databaseApp->id, $app->id);
        $this->assertEquals($this->databaseApp->key, $app->key);
        $this->assertEquals($this->databaseApp->secret, $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }

    /** @test */
    public function it_can_find_app_by_key()
    {
        $app = $this->databaseAppProvider->findByKey('InvalidKey');

        $this->assertNull($app);

        $app = $this->databaseAppProvider->findByKey($this->databaseApp->key);

        $this->assertEquals($this->databaseApp->name, $app->name);
        $this->assertEquals($this->databaseApp->id, $app->id);
        $this->assertEquals($this->databaseApp->key, $app->key);
        $this->assertEquals($this->databaseApp->secret, $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }

    /** @test */
    public function it_can_find_app_by_secret()
    {
        $app = $this->databaseAppProvider->findBySecret('InvalidSecret');

        $this->assertNull($app);

        $app = $this->databaseAppProvider->findBySecret($this->databaseApp->secret);

        $this->assertEquals($this->databaseApp->name, $app->name);
        $this->assertEquals($this->databaseApp->id, $app->id);
        $this->assertEquals($this->databaseApp->key, $app->key);
        $this->assertEquals($this->databaseApp->secret, $app->secret);
        $this->assertFalse($app->clientMessagesEnabled);
        $this->assertTrue($app->statisticsEnabled);
    }
}
