<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Rules;

use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;

class AppIdTest extends TestCase
{
    /** @test */
    public function it_can_validate_an_app_id()
    {
        $rule = new AppId();

        $this->assertTrue($rule->passes('app_id', config('websockets.apps.0.id')));
        $this->assertFalse($rule->passes('app_id', 'invalid-app-id'));
    }
}
