<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\StudyPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

// Manages the student's account settings: preferences and password.
class SettingsController extends Controller
{
    /**
     * Show the account settings: study preferences and password.
     */
    public function edit(Request $request): View
    {
        // Pass the current values and the option lists to the view.
        return view('account.settings', [
            'user' => $request->user(),
            // The selectable landing-page options.
            'landingPages' => StudyPreferences::LANDING_PAGES,
            // The available practice-timer options.
            'timeLimitChoices' => StudyPreferences::TIME_LIMIT_CHOICES,
            // The timer option matching the stored value, to pre-select it.
            'timeLimitChoice' => StudyPreferences::timeLimitChoice($request->user()->practice_time_limit_minutes),
        ]);
    }

    /**
     * Save the student's study preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Daily item target is required, whole, and between 5 and 500.
            'daily_target_items' => ['required', 'integer', 'min:5', 'max:500'],
            // The timer and landing page must each be a known choice.
            'practice_time_limit' => ['required', Rule::in(StudyPreferences::timeLimitChoices())],
            'default_landing_page' => ['required', Rule::in(StudyPreferences::landingPages())],
        ]);

        // Save the preferences onto the user.
        $request->user()->update([
            'daily_target_items' => $validated['daily_target_items'],
            // Convert the timer choice into the stored minutes value (null/0/int).
            'practice_time_limit_minutes' => StudyPreferences::timeLimitValue($validated['practice_time_limit']),
            // The chosen landing page key.
            'default_landing_page' => $validated['default_landing_page'],
        ]);

        return redirect()
            ->route('account.settings')
            ->with('status', 'Your study preferences have been saved.');
    }

    /**
     * Change the student's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // The current password must match the stored password.
            'current_password' => ['required', 'current_password'],
            // The new password must be confirmed and meet the app's rules.
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Save the new password; the User model hashes it through a cast.
        $request->user()->update(['password' => $validated['password']]);

        return redirect()
            ->route('account.settings')
            ->with('status', 'Your password has been changed.');
    }
}
