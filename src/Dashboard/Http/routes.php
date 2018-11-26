<?php

use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;

Route::get('/', ShowDashboard::class);
Route::post('/auth', AuthenticateDashboard::class);
Route::post('/event', SendMessage::class);