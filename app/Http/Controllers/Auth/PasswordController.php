<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\Auth\PasswordChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Change the signed-in user's password.
     *
     * The security alert is queued and best-effort: if it cannot be queued (or
     * sent, on the sync driver) the error is reported, but must not turn an
     * already-saved password change into an error.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        rescue(fn () => $request->user()->notify(new PasswordChangedNotification));

        return back()->with('status', 'password-updated');
    }
}
