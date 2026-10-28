<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LetTrack;
use App\Support\SystemSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

// Handles self-service account registration.
class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): View|RedirectResponse
    {
        // Registration can be switched off by an administrator.
        if (! SystemSettings::registrationEnabled()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Registration is closed at the moment. Please try again later.']);
        }

        // Otherwise show the registration form with its select options.
        return view('auth.register', [
            // The selectable LET exam types (elementary/secondary).
            'examTypes' => LetTrack::EXAM_TYPES,
            // The specialization options for each exam type.
            'specializations' => LetTrack::SPECIALIZATIONS,
        ]);
    }

    /**
     * Create a new account and sign the user in.
     */
    public function store(Request $request): RedirectResponse
    {
        // Check again that registration is still open (it can change at any time).
        if (! SystemSettings::registrationEnabled()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Registration is closed at the moment. Please try again later.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            // Email must be a unique, lowercase, valid address.
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            // Exam type must be one of the known LET exam types.
            'exam_type' => ['required', Rule::in(array_keys(LetTrack::EXAM_TYPES))],
            // Specialization must belong to the submitted exam type.
            'specialization' => [
                'required',
                'string',
                Rule::in(LetTrack::specializations($request->input('exam_type'))),
            ],
            // The password must be confirmed and meet the app's rules.
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // New accounts start with the study defaults set by the administrator.
        $validated['daily_target_items'] = SystemSettings::defaultDailyTargetItems();
        $validated['practice_time_limit_minutes'] = SystemSettings::defaultPracticeTimeLimitMinutes();

        // Create the account from the validated data.
        $user = User::create($validated);

        // Fire the Registered event (for listeners such as the verification email).
        event(new Registered($user));

        // Sign the new user in and rotate the session id.
        Auth::login($user);
        $request->session()->regenerate();

        // Send the new user to their dashboard.
        return redirect()->route('dashboard');
    }
}
