<?php

// Groups this controller with the other authentication controllers.
namespace App\Http\Controllers\Auth;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// StudyPreferences resolves the student's chosen landing route after login.
use App\Support\StudyPreferences;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and session).
use Illuminate\Http\Request;
// Auth is the authentication facade used to log users in and out.
use Illuminate\Support\Facades\Auth;
// ValidationException lets us raise our own validation errors manually.
use Illuminate\Validation\ValidationException;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Handles signing in and signing out (Laravel's "authenticated session").
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the sign-in form.
     */
    // Route action for GET /login.
    public function create(): View
    {
        // Render the login Blade view.
        return view('auth.login');
    }

    /**
     * Attempt to sign the user in.
     */
    // Route action for POST /login.
    public function store(Request $request): RedirectResponse
    {
        // Validate the login credentials before attempting authentication.
        $credentials = $request->validate([
            // Email must be present, text, and email-shaped.
            'email' => ['required', 'string', 'email'],
            // Password must be present and text.
            'password' => ['required', 'string'],
        ]);

        // Attempt to authenticate; "remember" comes from the login checkbox.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // On failure, raise a validation error on the email field.
            throw ValidationException::withMessages([
                // Localised "these credentials do not match" message.
                'email' => __('auth.failed'),
            ]);
        }

        // Check whether the authenticated account has been suspended.
        if ($request->user()->isSuspended()) {
            // Log the suspended user straight back out.
            Auth::guard('web')->logout();
            // Flush the session so no data survives.
            $request->session()->invalidate();
            // Rotate the CSRF token for safety.
            $request->session()->regenerateToken();

            // Report the suspension to the user as a validation error.
            throw ValidationException::withMessages([
                // Message shown against the email field.
                'email' => 'This account has been suspended. Please contact the administrator.',
            ]);
        }

        // Rotate the session id to prevent session fixation after login.
        $request->session()->regenerate();

        // Redirect to the intended URL, or the user's chosen landing route.
        return redirect()->intended(route(StudyPreferences::landingRoute($request->user()->default_landing_page)));
    }

    /**
     * Sign the user out.
     */
    // Route action for POST /logout.
    public function destroy(Request $request): RedirectResponse
    {
        // Log the user out of the default "web" guard.
        Auth::guard('web')->logout();

        // Flush the session data.
        $request->session()->invalidate();
        // Rotate the CSRF token.
        $request->session()->regenerateToken();

        // Send the user home with a confirmation flash message.
        return redirect()->route('home')->with('status', 'You have been signed out.');
    }
}
