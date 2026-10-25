<?php

// Groups this controller with the other student "Account" controllers.
namespace App\Http\Controllers\Account;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// StudyPreferences exposes the landing-page and timer option lists.
use App\Support\StudyPreferences;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and signed-in user).
use Illuminate\Http\Request;
// Rule builds validation rules such as "in" for allowed values.
use Illuminate\Validation\Rule;
// Password is a ready-made rule object for strong-password validation.
use Illuminate\Validation\Rules\Password;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Manages the student's account settings: preferences and password.
class SettingsController extends Controller
{
    /**
     * Display the account settings: study preferences and password.
     */
    // Route action for GET /account/settings.
    public function edit(Request $request): View
    {
        // Render the settings view with the current values and option lists.
        return view('account.settings', [
            // The signed-in student being edited.
            'user' => $request->user(),
            // The selectable landing-page options.
            'landingPages' => StudyPreferences::LANDING_PAGES,
            // The available practice-timer options.
            'timeLimitChoices' => StudyPreferences::TIME_LIMIT_CHOICES,
            // The timer option that matches the stored value, to pre-select it.
            'timeLimitChoice' => StudyPreferences::timeLimitChoice($request->user()->practice_time_limit_minutes),
        ]);
    }

    /**
     * Save the student's study preferences.
     */
    // Route action for saving study preferences.
    public function updatePreferences(Request $request): RedirectResponse
    {
        // Validate the preference fields; only validated keys are returned.
        $validated = $request->validate([
            // Daily item target is required, whole, and between 5 and 500.
            'daily_target_items' => ['required', 'integer', 'min:5', 'max:500'],
            // Practice timer must be one of the known timer choices.
            'practice_time_limit' => ['required', Rule::in(StudyPreferences::timeLimitChoices())],
            // Landing page must be one of the known landing-page choices.
            'default_landing_page' => ['required', Rule::in(StudyPreferences::landingPages())],
        ]);

        // Save the preferences onto the user (mass assignment through update()).
        $request->user()->update([
            // The chosen daily item target, stored as-is.
            'daily_target_items' => $validated['daily_target_items'],
            // Convert the timer choice into the stored minutes value (null/0/int).
            'practice_time_limit_minutes' => StudyPreferences::timeLimitValue($validated['practice_time_limit']),
            // The chosen landing page key.
            'default_landing_page' => $validated['default_landing_page'],
        ]);

        // Redirect back to settings with a success message.
        return redirect()
            // Named route for the settings page.
            ->route('account.settings')
            // Flash message for the next request.
            ->with('status', 'Your study preferences have been saved.');
    }

    /**
     * Change the student's password.
     */
    // Route action for changing the password.
    public function updatePassword(Request $request): RedirectResponse
    {
        // Validate the password-change fields.
        $validated = $request->validate([
            // Current password must be present and match the stored password.
            'current_password' => ['required', 'current_password'],
            // New password is required, must be confirmed, and meets the app's rules.
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Save the new password; the User model hashes it via a cast.
        $request->user()->update(['password' => $validated['password']]);

        // Redirect back to settings with a success message.
        return redirect()
            // Named route for the settings page.
            ->route('account.settings')
            // Flash message for the next request.
            ->with('status', 'Your password has been changed.');
    }
}
