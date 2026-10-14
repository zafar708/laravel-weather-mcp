<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

it('renders the verification notice for an unverified user', function () {
    $user = User::factory()->unverified()->create(['email' => 'new@example.test']);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('new@example.test');
});

it('redirects a verified user away from the verification notice', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

it('sends an unverified user to the verification notice instead of the dashboard', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('verifies the email address from the signed link', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();
    $url = (new VerifyEmailNotification)->verificationUrl($user);

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('sends a welcome email once the address is verified', function () {
    Mail::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get((new VerifyEmailNotification)->verificationUrl($user));

    Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $mail): bool => $mail->hasTo($user->email));
});

it('rejects a link whose hash does not match the email', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.test'),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an expired verification link', function () {
    $user = User::factory()->unverified()->create();
    $url = (new VerifyEmailNotification)->verificationUrl($user);

    $this->travel(61)->minutes();

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email on request', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'A new verification link has been sent to your email address.');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('does not resend the verification email to a verified user', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create())
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});

it('renders the verification email with the user name and link', function () {
    $user = User::factory()->unverified()->create(['name' => 'Amina Khan']);

    $html = (string) (new VerifyEmailNotification)->toMail($user)->render();

    expect($html)
        ->toContain('Hi Amina Khan')
        ->toContain('Verify email address')
        ->toContain("/verify-email/{$user->id}/");
});

it('renders the welcome email with a link to the dashboard', function () {
    $user = User::factory()->create(['name' => 'Amina Khan']);

    $mailable = new WelcomeMail($user);

    $mailable->assertHasSubject('Welcome to '.config('app.name'));
    $mailable->assertSeeInHtml('Welcome aboard, Amina Khan!');
    $mailable->assertSeeInHtml(route('dashboard'));
});
