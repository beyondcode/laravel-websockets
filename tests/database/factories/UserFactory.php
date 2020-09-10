<?php
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

use Illuminate\Support\Str;

$factory->define(\BeyondCode\LaravelWebSockets\Test\Models\User::class, function () {
    return [
        'name' => 'Name'.Str::random(5),
        'email' => Str::random(5).'@gmail.com',
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'remember_token' => Str::random(10),
    ];
});
