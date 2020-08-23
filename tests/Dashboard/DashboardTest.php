<?php

namespace BeyondCode\LaravelWebSockets\Tests\Dashboard;

use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Tests\Models\User;

class DashboardTest extends TestCase
{
    /** @test */
    public function cant_see_dashboard_without_authorization()
    {
        $this->get(route('laravel-websockets.dashboard'))
            ->assertResponseStatus(403);
    }

    /** @test */
    public function can_see_dashboard()
    {
        $this->actingAs(factory(User::class)->create())
            ->get(route('laravel-websockets.dashboard'))
            ->assertResponseOk()
            ->see('WebSockets Dashboard');
    }
}
