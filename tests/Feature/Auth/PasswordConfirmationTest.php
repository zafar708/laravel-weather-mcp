<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('asks for the password before opening account settings', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertRedirect(route('password.confirm'));
});

it('renders the password confirmation page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee('Confirm your password');
});

it('confirms the password and continues to the intended page', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['url.intended' => route('profile.edit')])
        ->post(route('password.confirm'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('auth.password_confirmed_at');
});

it('rejects an incorrect password', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors(['password' => 'The provided password is incorrect.'])
        ->assertSessionMissing('auth.password_confirmed_at');
});
