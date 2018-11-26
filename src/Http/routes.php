<?php

use BeyondCode\LaravelWebsockets\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebsockets\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebsockets\Http\Controllers\ShowDashboard;

Route::get('/', ShowDashboard::class);
Route::post('/auth', AuthenticateDashboard::class);
Route::post('/event', SendMessage::class);