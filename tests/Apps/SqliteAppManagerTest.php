<?php

namespace BeyondCode\LaravelWebSockets\Test\Apps;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Apps\SQLiteAppManager;
use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use BeyondCode\LaravelWebSockets\Test\TestCase;

class SqliteAppManagerTest extends TestCase
{
    /** @var AppManager */
    protected $apps;

    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('websockets.managers.app', SQLiteAppManager::class);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->apps = app()->make(AppManager::class);
    }

    public function test_can_return_all_apps()
    {
        $apps = $this->await($this->apps->all());
        $this->assertCount(0, $apps);

        $this->await($this->apps->createApp([
            'id' => 1,
            'key' => 'test',
            'secret' => 'secret',
            'name' => 'Test',
            'enable_client_messages' => true,
            'enable_statistics' => false,
        ]));

        $apps = $this->await($this->apps->all());
        $this->assertCount(1, $apps);
    }

    public function test_can_find_apps_by_id()
    {
        $this->await($this->apps->createApp([
            'id' => 1,
            'key' => 'test',
            'secret' => 'secret',
            'name' => 'Test',
            'enable_client_messages' => true,
            'enable_statistics' => false,
        ]));

        $app = $this->await($this->apps->findById(1));

        $this->assertInstanceOf(App::class, $app);
        $this->assertSame('test', $app->key);
    }

    public function test_can_find_apps_by_key()
    {
        $this->await($this->apps->createApp([
            'id' => 1,
            'key' => 'key',
            'secret' => 'secret',
            'name' => 'Test',
            'enable_client_messages' => true,
            'enable_statistics' => false,
        ]));

        $app = $this->await($this->apps->findByKey('key'));

        $this->assertInstanceOf(App::class, $app);
        $this->assertSame('key', $app->key);
    }

    public function test_can_find_apps_by_secret()
    {
        $this->await($this->apps->createApp([
            'id' => 1,
            'key' => 'key',
            'secret' => 'secret',
            'name' => 'Test',
            'enable_client_messages' => true,
            'enable_statistics' => false,
        ]));

        $app = $this->await($this->apps->findBySecret('secret'));

        $this->assertInstanceOf(App::class, $app);
        $this->assertSame('key', $app->key);
    }
}
