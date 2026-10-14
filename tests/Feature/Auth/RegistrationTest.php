<?php

use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

it('renders the registration page', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create your account');
});

it('registers a user, signs them in and emails a verification link', function () {
    Notification::fake();

    $response = $this->post(route('register'), [
        'name' => 'Amina Khan',
        'email' => 'amina@example.test',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::firstWhere('email', 'amina@example.test');

    expect($user)->not->toBeNull()
        ->name->toBe('Amina Khan')
        ->email_verified_at->toBeNull();
    expect(Hash::check('secret-password', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('requires a name, email and password', function () {
    $this->post(route('register'), [])
        ->assertSessionHasErrors(['name', 'email', 'password']);

    $this->assertGuest();
});

it('rejects an email that is already registered', function () {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->post(route('register'), [
        'name' => 'Someone Else',
        'email' => 'taken@example.test',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors(['email' => 'The email has already been taken.']);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

it('rejects a password that does not match its confirmation', function () {
    $this->post(route('register'), [
        'name' => 'Amina Khan',
        'email' => 'amina@example.test',
        'password' => 'secret-password',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors(['password' => 'The password field confirmation does not match.']);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'amina@example.test']);
});
