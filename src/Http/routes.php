<?php

Route::get('/', 'ShowConsole');
Route::post('/auth', 'AuthenticateConsole');
Route::post('/event', 'SendMessage');