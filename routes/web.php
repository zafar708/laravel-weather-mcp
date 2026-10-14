<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
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

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->middleware('password.confirm')
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('push-subscriptions.destroy');
});

require __DIR__.'/auth.php';
