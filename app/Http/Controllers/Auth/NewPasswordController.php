<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
// Aliased because the Password facade has the same base name.
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

// Handles the second half of the "forgot password" flow: setting a new password.
class NewPasswordController extends Controller
{
    /**
     * Show the password reset form for the given token.
     */
    public function create(Request $request): View
    {
        // Pass the request through so the form can read the token and email.
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Save the new password for the given email address.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            // The new password must be confirmed and meet the app's rules.
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Try the reset. The callback runs only when the token is valid.
        $status = Password::reset(
            // Only these four fields are handed to the broker.
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // Save the new password (hashed) and a fresh remember token.
                // forceFill skips mass-assignment guards.
                $user->forceFill([
                    'password' => Hash::make($password),
                    // Replace the remember token so old "remember me" sessions die.
                    'remember_token' => Str::random(60),
                ])->save();

                // Fire the PasswordReset event (for listeners/notifications).
                event(new PasswordReset($user));
            }
        );

        // On success, go to the login page with the localised status message.
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        // Otherwise, go back with the email kept and the error shown.
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
