<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/mcp-tester', 'mcp-tester');

Route::view('/users-chat', 'users-chat')->name('users-chat');
