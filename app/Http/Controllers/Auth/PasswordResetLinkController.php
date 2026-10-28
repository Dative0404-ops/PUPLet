<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Handles the first half of the "forgot password" flow: requesting a reset link.
class PasswordResetLinkController extends Controller
{
    /**
     * Show the "forgot password" form.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given email address.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Ask the password broker to email a reset link for that address.
        $status = Password::sendResetLink($request->only('email'));

        // When the link was sent, go back with a status message.
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        // Otherwise, raise a validation error explaining the failure.
        throw ValidationException::withMessages([
            'email' => __($status),
        ]);
    }
}
