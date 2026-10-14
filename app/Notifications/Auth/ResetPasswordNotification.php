<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $token) {}

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
        $broker = config('auth.defaults.passwords');

        return (new MailMessage)
            ->subject('Reset your password')
            ->markdown('mail.auth.reset-password', [
                'name' => $notifiable->name,
                'url' => $this->resetUrl($notifiable),
                'expiresInMinutes' => (int) config("auth.passwords.{$broker}.expire", 60),
            ]);
    }

    /**
     * Build the password reset link for the user.
     */
    public function resetUrl(User $notifiable): string
    {
        return route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
