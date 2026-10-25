<?php

// Groups this controller with the other authentication controllers.
namespace App\Http\Controllers\Auth;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input).
use Illuminate\Http\Request;
// Password is the service that emails the reset link.
use Illuminate\Support\Facades\Password;
// ValidationException lets us raise our own validation errors manually.
use Illuminate\Validation\ValidationException;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Handles the first half of the "forgot password" flow: requesting a reset link.
class PasswordResetLinkController extends Controller
{
    /**
     * Display the "forgot password" form.
     */
    // Route action for GET /forgot-password.
    public function create(): View
    {
        // Render the forgot-password Blade view.
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given email address.
     */
    // Route action for POST /forgot-password.
    public function store(Request $request): RedirectResponse
    {
        // Validate that an email address was supplied.
        $request->validate([
            // Email must be present, text, and email-shaped.
            'email' => ['required', 'string', 'email'],
        ]);

        // Ask the password broker to email a reset link for that address.
        $status = Password::sendResetLink($request->only('email'));

        // When the link was sent, return to the form with a status message.
        if ($status === Password::RESET_LINK_SENT) {
            // Back to the same page, showing the localised status text.
            return back()->with('status', __($status));
        }

        // Otherwise, raise a validation error explaining the failure.
        throw ValidationException::withMessages([
            // The failure reason is shown against the email field.
            'email' => __($status),
        ]);
    }
}
