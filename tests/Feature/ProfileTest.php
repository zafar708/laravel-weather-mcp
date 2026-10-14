<?php

use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

it('renders the account settings page', function () {
    $user = User::factory()->create(['email' => 'amina@example.test']);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Account settings')
        ->assertSee('value="amina@example.test"', false);
});

it('updates the name and keeps an unchanged email verified', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'New Name', 'email' => $user->email])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'profile-updated');

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('requires re-verification when the email changes', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => 'changed@example.test'])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->email)->toBe('changed@example.test')
        ->and($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('rejects an email that belongs to another user', function () {
    User::factory()->create(['email' => 'taken@example.test']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => 'taken@example.test'])
        ->assertSessionHasErrors(['email' => 'The email has already been taken.']);

    expect($user->fresh()->email)->not->toBe('taken@example.test');
});

it('deletes the account when the password is confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($user);
});

it('keeps the account when the password is wrong', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrorsIn('userDeletion', ['password' => 'The password is incorrect.']);

    $this->assertAuthenticatedAs($user);
    $this->assertModelExists($user);
});
