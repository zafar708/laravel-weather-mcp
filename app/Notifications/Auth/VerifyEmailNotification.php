<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your email address')
            ->markdown('mail.auth.verify-email', [
                'name' => $notifiable->name,
                'url' => $this->verificationUrl($notifiable),
                'expiresInMinutes' => $this->expiresInMinutes(),
            ]);
    }

    /**
     * Build the signed, expiring verification link for the user.
     */
    public function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($this->expiresInMinutes()),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }

    /**
     * Get how long the verification link stays valid.
     */
    protected function expiresInMinutes(): int
    {
        return (int) config('auth.verification.expire', 60);
    }
}
