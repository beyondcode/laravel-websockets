<?php

namespace BeyondCode\LaravelWebSockets\Test\Dashboard;

use BeyondCode\LaravelWebSockets\Test\Models\User;
use BeyondCode\LaravelWebSockets\Test\TestCase;

class DashboardTest extends TestCase
{
    public function test_cant_see_dashboard_without_authorization()
    {
        $this->get(route('laravel-websockets.dashboard'))
            ->assertResponseStatus(403);
    }

    public function test_can_see_dashboard()
    {
        $this->actingAs(factory(User::class)->create())
            ->get(route('laravel-websockets.dashboard'))
            ->assertResponseOk()
            ->see('WebSockets Dashboard');
    }
}
