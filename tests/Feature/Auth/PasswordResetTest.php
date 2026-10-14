<?php

use App\Models\User;
use App\Notifications\Auth\PasswordChangedNotification;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

it('renders the forgot password page', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Forgot your password?');
});

it('emails a reset link to a registered address', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status', 'We have emailed your password reset link.');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('rejects an address that does not belong to any account', function () {
    Notification::fake();
    User::factory()->create(['email' => 'amina@example.test']);

    $this->post(route('password.email'), ['email' => 'nobody@example.test'])
        ->assertSessionHasErrors(['email' => "We can't find a user with that email address."])
        ->assertSessionMissing('status');

    Notification::assertNothingSent();
});

it('rejects a second reset request made too soon', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors(['email' => 'Please wait before retrying.']);

    Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
});

it('renders the reset form with the email prefilled', function () {
    $this->get(route('password.reset', ['token' => 'reset-token', 'email' => 'amina@example.test']))
        ->assertOk()
        ->assertSee('value="reset-token"', false)
        ->assertSee('value="amina@example.test"', false);
});

it('resets the password with a valid token and sends a security alert', function () {
    Notification::fake();
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Your password has been reset.');

    expect(Hash::check('new-secret-password', $user->fresh()->password))->toBeTrue();
    Notification::assertSentTo($user, PasswordChangedNotification::class);
});

it('rejects an invalid reset token', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])->assertSessionHasErrors(['email' => 'This password reset token is invalid.']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    Notification::assertNothingSent();
});

it('renders the reset email with the reset link', function () {
    $user = User::factory()->create(['name' => 'Amina Khan']);

    $html = (string) (new ResetPasswordNotification('reset-token'))->toMail($user)->render();

    expect($html)
        ->toContain('Hi Amina Khan')
        ->toContain('/reset-password/reset-token?email=');
});
