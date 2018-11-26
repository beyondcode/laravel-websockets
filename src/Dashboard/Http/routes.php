<?php

use BeyondCode\LaravelWebsockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebsockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebsockets\Dashboard\Http\Controllers\ShowDashboard;

Route::get('/', ShowDashboard::class);
Route::post('/auth', AuthenticateDashboard::class);
Route::post('/event', SendMessage::class);