<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * The web push channel only delivers to browsers where the user has
     * enabled desktop notifications, so it is safe to always include it.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', WebPushChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your password was changed')
            ->markdown('mail.auth.password-changed', [
                'name' => $notifiable->name,
                'changedAt' => now()->toDayDateTimeString(),
                'resetUrl' => route('password.request'),
            ]);
    }

    /**
     * Get the desktop (web push) representation of the notification.
     */
    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Your password was changed')
            ->body('Your '.config('app.name')." password was just changed. If this wasn't you, reset it right away.")
            ->icon(asset('favicon.ico'))
            ->tag('password-changed')
            ->requireInteraction()
            ->data(['url' => route('password.request')]);
    }
}
