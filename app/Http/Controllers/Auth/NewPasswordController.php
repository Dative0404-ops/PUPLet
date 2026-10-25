<?php

// Groups this controller with the other authentication controllers.
namespace App\Http\Controllers\Auth;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The User model type-hinted inside the password-reset callback below.
use App\Models\User;
// PasswordReset is the event broadcast after a password is reset.
use Illuminate\Auth\Events\PasswordReset;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input).
use Illuminate\Http\Request;
// Hash hashes the new password before it is stored.
use Illuminate\Support\Facades\Hash;
// Password is the service that validates reset tokens and performs the reset.
use Illuminate\Support\Facades\Password;
// Str generates a random string for the new remember token.
use Illuminate\Support\Str;
// PasswordRule is aliased because Password (the facade) has the same base name.
use Illuminate\Validation\Rules\Password as PasswordRule;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Handles the second half of the "forgot password" flow: setting a new password.
class NewPasswordController extends Controller
{
    /**
     * Display the password reset form for the given token.
     */
    // Route action for GET /reset-password/{token}.
    public function create(Request $request): View
    {
        // Render the reset form, passing the request through for the token/email.
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Store the new password for the given email address.
     */
    // Route action for POST /reset-password.
    public function store(Request $request): RedirectResponse
    {
        // Validate the reset submission before touching the password service.
        $request->validate([
            // The reset token must be present.
            'token' => ['required'],
            // Email must be present, text, and email-shaped.
            'email' => ['required', 'string', 'email'],
            // New password is required, must be confirmed, and meets the app rules.
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Attempt the reset, passing a callback that runs only when it succeeds.
        $status = Password::reset(
            // Only these four fields are handed to the broker.
            $request->only('email', 'password', 'password_confirmation', 'token'),
            // The callback that receives the matched user and the new plain password.
            function (User $user, string $password) {
                // Write the new password and a fresh remember token; forceFill bypasses guards.
                $user->forceFill([
                    // Store the password already hashed.
                    'password' => Hash::make($password),
                    // Replace the remember token so old "remember me" sessions die.
                    'remember_token' => Str::random(60),
                ])->save();

                // Fire the PasswordReset event (e.g. for listeners/notifications).
                event(new PasswordReset($user));
            }
        );

        // A successful reset sends the user to the login page with a status message.
        if ($status === Password::PASSWORD_RESET) {
            // Named route for the login page, with the localised status text.
            return redirect()->route('login')->with('status', __($status));
        }

        // Otherwise, bounce back with the email kept and the error shown.
        return back()
            // Preserve the email so the user does not retype it.
            ->withInput($request->only('email'))
            // Show the localised failure reason against the email field.
            ->withErrors(['email' => __($status)]);
    }
}
