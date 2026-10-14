<?php

use App\Models\User;
use App\Notifications\Auth\PasswordChangedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use NotificationChannels\WebPush\WebPushChannel;

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
    Notification::assertSentTo(
        $user,
        PasswordChangedNotification::class,
        fn (PasswordChangedNotification $notification, array $channels): bool => $channels === ['mail', WebPushChannel::class],
    );
});

it('queues the security alert instead of sending it during the request', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertSessionHasNoErrors();

    Queue::assertPushed(
        SendQueuedNotifications::class,
        fn (SendQueuedNotifications $job): bool => $job->notification instanceof PasswordChangedNotification
            && $job->notifiables->first()->is($user),
    );
});

it('still changes the password when the desktop alert cannot be delivered', function () {
    Exceptions::fake();
    $this->mock(WebPushChannel::class)
        ->shouldReceive('send')
        ->andThrow(new RuntimeException('Unable to create the local key.'));
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
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Unable to create the local key.');
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

it('builds a desktop alert that stays on screen and links to password reset', function () {
    $user = User::factory()->create();
    $notification = new PasswordChangedNotification;

    $payload = $notification->toWebPush($user, $notification)->toArray();

    expect($payload)
        ->title->toBe('Your password was changed')
        ->body->toContain("If this wasn't you, reset it right away.")
        ->tag->toBe('password-changed')
        ->requireInteraction->toBeTrue()
        ->data->toBe(['url' => route('password.request')]);
});
