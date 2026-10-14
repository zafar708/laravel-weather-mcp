<?php

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

it('renders the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Welcome back');
});

it('signs in with valid credentials and redirects to the dashboard', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('returns the user to the page they originally requested', function () {
    $user = User::factory()->create();

    $this->get(route('profile.edit'))->assertRedirect(route('login'));

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('profile.edit'));
});

it('rejects an incorrect password', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

    $this->assertGuest();
});

it('locks the login after too many failed attempts', function () {
    Event::fake([Lockout::class]);
    $user = User::factory()->create();

    foreach (range(1, LoginRequest::MaxAttempts) as $attempt) {
        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toStartWith('Too many login attempts.');
    $this->assertGuest();
    Event::assertDispatched(Lockout::class);
});

it('remembers the user when requested', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => '1',
    ])->assertCookie(Auth::guard()->getRecallerName());
});

it('lets a freshly signed-in user open account settings without re-entering the password', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->get(route('profile.edit'))->assertOk();
});

it('redirects signed-in users away from the login page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

it('redirects guests from the dashboard to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('signs the user out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});
