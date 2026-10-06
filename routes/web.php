<?php

use App\Http\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/mcp-tester', 'mcp-tester');

Route::view('/users-chat', 'users-chat')->name('users-chat');

Route::view('/ai-chat', 'ai-chat')->name('ai-chat');

Route::post('/ai-chat', AiChatController::class)
    ->middleware('throttle:20,1')
    ->name('ai-chat.send');
