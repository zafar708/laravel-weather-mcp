<?php

namespace App\Listeners;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    /**
     * Welcome the user once their email address has been verified.
     */
    public function handle(Verified $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        Mail::to($event->user)->send(new WelcomeMail($event->user));
    }
}
