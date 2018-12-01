<?php

namespace BeyondCode\LaravelWebSockets\Tests\ClientProviders;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Exceptions\InvalidApp;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class ClientTest extends TestCase
{
    /** @test */
    public function it_can_create_a_client()
    {
        new App(1, 'appKey', 'appSecret', 'new');

        $this->markTestAsPassed();
    }

    /** @test */
    public function it_will_not_accept_an_empty_appKey()
    {
        $this->expectException(InvalidApp::class);

        new App(1, '', 'appSecret', 'new');
    }

    /** @test */
    public function it_will_not_accept_an_empty_appSecret()
    {
        $this->expectException(InvalidApp::class);

        new App(1, 'appKey', '', 'new');
    }

    /** @test */
    public function it_will_not_accept_an_non_numeric_appId()
    {
        $this->expectException(InvalidApp::class);

        new App('appId', 'appKey', '', 'new');
    }
}