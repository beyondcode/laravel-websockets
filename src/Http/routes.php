<?php

use BeyondCode\LaravelWebsockets\Http\Controllers\AuthenticateConsole;
use BeyondCode\LaravelWebsockets\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebsockets\Http\Controllers\ShowConsole;

Route::get('/', ShowConsole::class);
Route::post('/auth', AuthenticateConsole::class);
Route::post('/event', SendMessage::class);