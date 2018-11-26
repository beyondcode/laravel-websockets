<?php

use BeyondCode\LaravelWebSockets\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Http\Controllers\ShowDashboard;

Route::get('/', ShowDashboard::class);
Route::post('/auth', AuthenticateDashboard::class);
Route::post('/event', SendMessage::class);