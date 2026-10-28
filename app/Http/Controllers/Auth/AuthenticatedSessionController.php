<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\StudyPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Handles signing in and signing out.
class AuthenticatedSessionController extends Controller
{
    /**
     * Show the sign-in form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Try to sign the user in.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Try to authenticate; "remember" comes from the login checkbox.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // On failure, raise a validation error on the email field.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // A suspended account is logged straight back out.
        if ($request->user()->isSuspended()) {
            Auth::guard('web')->logout();
            // Clear the session and rotate the CSRF token.
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account has been suspended. Please contact the administrator.',
            ]);
        }

        // Rotate the session id to prevent session fixation after login.
        $request->session()->regenerate();

        // Go to the intended URL, or the user's chosen landing route.
        return redirect()->intended(route(StudyPreferences::landingRoute($request->user()->default_landing_page)));
    }

    /**
     * Sign the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Log out of the default "web" guard.
        Auth::guard('web')->logout();

        // Clear the session and rotate the CSRF token.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Go home with a confirmation message.
        return redirect()->route('home')->with('status', 'You have been signed out.');
    }
}
