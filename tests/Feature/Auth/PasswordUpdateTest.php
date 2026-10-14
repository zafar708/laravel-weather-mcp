<?php

use App\Models\User;
use App\Notifications\Auth\PasswordChangedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

it('changes the password and sends a security alert', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'password-updated');

    expect(Hash::check('new-secret-password', $user->fresh()->password))->toBeTrue();
    Notification::assertSentTo($user, PasswordChangedNotification::class);
});

it('requires the correct current password', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'The password is incorrect.']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    Notification::assertNothingSent();
});

it('renders the password changed email with a reset link', function () {
    $user = User::factory()->create(['name' => 'Amina Khan']);

    $html = (string) (new PasswordChangedNotification)->toMail($user)->render();

    expect($html)
        ->toContain('Hi Amina Khan')
        ->toContain(route('password.request'));
});
