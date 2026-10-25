<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// StudyPreferences supplies the valid practice-timer choices.
use App\Support\StudyPreferences;
// SystemSettings holds and persists the site-wide settings.
use App\Support\SystemSettings;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and signed-in user).
use Illuminate\Http\Request;
// Rule builds the "in" rule for the practice-timer choice.
use Illuminate\Validation\Rule;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * The settings that apply to the whole site: how it introduces itself, the
 * defaults new accounts start with, and whether registration is open.
 */
// Admins edit the global, site-wide settings here.
class SystemSettingsController extends Controller
{
    /**
     * The settings currently in force.
     */
    // Route action for GET /admin/system-settings.
    public function edit(): View
    {
        // Render the settings form pre-filled with the current values.
        return view('admin.system-settings', [
            // The stored settings, formatted for the form inputs.
            'settings' => SystemSettings::values(),
            // The available practice-timer options for the dropdown.
            'timerChoices' => StudyPreferences::TIME_LIMIT_CHOICES,
        ]);
    }

    /**
     * Save the site-wide settings.
     */
    // Route action for PUT/PATCH /admin/system-settings.
    public function update(Request $request): RedirectResponse
    {
        // Validate every setting; only validated keys are returned.
        $data = $request->validate([
            // Site name is required text, up to 80 characters.
            'name' => ['required', 'string', 'max:80'],
            // Support email is required and must be a valid address.
            'support_email' => ['required', 'string', 'email', 'max:150'],
            // Support phone is optional text.
            'support_phone' => ['nullable', 'string', 'max:40'],
            // Support hours is optional text.
            'support_hours' => ['nullable', 'string', 'max:80'],
            // Default daily item target, within the allowed range.
            'default_daily_target_items' => ['required', 'integer', 'min:'.SystemSettings::DAILY_TARGET_MIN, 'max:'.SystemSettings::DAILY_TARGET_MAX],
            // Default practice timer must be a known timer choice.
            'default_practice_timer' => ['required', Rule::in(StudyPreferences::timeLimitChoices())],
            // Mock exam time limit in minutes, within the allowed range.
            'mock_time_limit_minutes' => ['required', 'integer', 'min:'.SystemSettings::MOCK_TIME_LIMIT_MIN, 'max:'.SystemSettings::MOCK_TIME_LIMIT_MAX],
        ]);

        // Persist the settings; the registration checkbox is merged in as a boolean.
        SystemSettings::save($data + ['registration_enabled' => $request->boolean('registration_enabled')]);

        // Return to the previous page with a success message.
        return back()->with('status', 'The system settings have been saved.');
    }
}
