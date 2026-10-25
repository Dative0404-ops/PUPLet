<?php

// Groups this controller with the other authentication controllers.
namespace App\Http\Controllers\Auth;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The User model that new accounts are created from.
use App\Models\User;
// LetTrack holds the LET exam types and their specialization options.
use App\Support\LetTrack;
// SystemSettings exposes the site-wide defaults and the registration toggle.
use App\Support\SystemSettings;
// Registered is the event broadcast after a new account is created.
use Illuminate\Auth\Events\Registered;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and session).
use Illuminate\Http\Request;
// Auth is the authentication facade used to sign the new user in.
use Illuminate\Support\Facades\Auth;
// Rule builds the "in" rule for the exam type and specialization.
use Illuminate\Validation\Rule;
// Password is a ready-made rule object for strong-password validation.
use Illuminate\Validation\Rules\Password;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Handles self-service account registration.
class RegisteredUserController extends Controller
{
    /**
     * Display the registration form.
     */
    // Route action for GET /register; may also redirect when registration is closed.
    public function create(): View|RedirectResponse
    {
        // Registration may be switched off by an administrator.
        if (! SystemSettings::registrationEnabled()) {
            // Send the visitor to the login page with an explanatory error.
            return redirect()->route('login')
                // Show the message against the email field on the login form.
                ->withErrors(['email' => 'Registration is closed at the moment. Please try again later.']);
        }

        // Otherwise render the registration Blade view.
        return view('auth.register');
    }

    /**
     * Create a new account and sign the user in.
     */
    // Route action for POST /register.
    public function store(Request $request): RedirectResponse
    {
        // Re-check that registration is still open (it can change at any time).
        if (! SystemSettings::registrationEnabled()) {
            // Send the visitor to the login page with an explanatory error.
            return redirect()->route('login')
                // Show the message against the email field.
                ->withErrors(['email' => 'Registration is closed at the moment. Please try again later.']);
        }

        // Validate the registration fields; only validated keys are returned.
        $validated = $request->validate([
            // Name is required text, up to 100 characters.
            'name' => ['required', 'string', 'max:100'],
            // Email must be a unique, lowercase, valid address.
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            // Exam type must be one of the known LET exam types.
            'exam_type' => ['required', Rule::in(array_keys(LetTrack::EXAM_TYPES))],
            // Specialization must belong to the submitted exam type.
            'specialization' => [
                // Must be present.
                'required',
                // Must be a string.
                'string',
                // Must be one of the majors valid for the chosen exam type.
                Rule::in(LetTrack::specializations($request->input('exam_type'))),
            ],
            // Password is required, must be confirmed, and meets the app rules.
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // New accounts start with the study defaults the administrator has set.
        $validated['daily_target_items'] = SystemSettings::defaultDailyTargetItems();
        // Apply the configured default practice timer (stored as minutes).
        $validated['practice_time_limit_minutes'] = SystemSettings::defaultPracticeTimeLimitMinutes();

        // Create the account row from the validated data (mass assignment).
        $user = User::create($validated);

        // Fire the Registered event (e.g. for listeners such as verification email).
        event(new Registered($user));

        // Log the new user in immediately.
        Auth::login($user);
        // Rotate the session id to prevent session fixation.
        $request->session()->regenerate();

        // Send the new user to their dashboard.
        return redirect()->route('dashboard');
    }
}
