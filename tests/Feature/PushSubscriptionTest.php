<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{endpoint: string, keys: array{p256dh: string, auth: string}, content_encoding: string}
 */
function browserSubscription(string $endpoint = 'https://fcm.googleapis.com/fcm/send/browser-1'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => [
            'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
            'auth' => 'tBHItJI5svbpez7KI4CCXg',
        ],
        'content_encoding' => 'aes128gcm',
    ];
}

it('saves the browser subscription for the signed-in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), browserSubscription())
        ->assertCreated();

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_type' => $user->getMorphClass(),
        'subscribable_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/browser-1',
        'public_key' => browserSubscription()['keys']['p256dh'],
        'auth_token' => 'tBHItJI5svbpez7KI4CCXg',
        'content_encoding' => 'aes128gcm',
    ]);
});

it('rejects a subscription without encryption keys or with an insecure endpoint', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'http://push.example.test/insecure',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);

    $this->assertDatabaseCount('push_subscriptions', 0);
});

it('rejects subscription requests from guests', function () {
    $this->postJson(route('push-subscriptions.store'), browserSubscription())
        ->assertUnauthorized();

    $this->assertDatabaseCount('push_subscriptions', 0);
});

it('removes the browser subscription when the user disables notifications', function () {
    $user = User::factory()->create();
    $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/browser-1', 'key', 'token');

    $this->actingAs($user)
        ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/browser-1'])
        ->assertOk();

    $this->assertDatabaseCount('push_subscriptions', 0);
});

it('does not remove a subscription that belongs to another user', function () {
    $owner = User::factory()->create();
    $owner->updatePushSubscription('https://fcm.googleapis.com/fcm/send/owner-browser', 'key', 'token');

    $this->actingAs(User::factory()->create())
        ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/owner-browser'])
        ->assertOk();

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $owner->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/owner-browser',
    ]);
});
