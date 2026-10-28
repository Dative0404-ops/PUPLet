<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// Lists the valid practice-timer choices.
use App\Support\StudyPreferences;
// Holds and saves the site-wide settings.
use App\Support\SystemSettings;
// Returned when an action redirects the browser.
use Illuminate\Http\RedirectResponse;
// The current request: form input and signed-in user.
use Illuminate\Http\Request;
// Builds the "in" rule for the practice-timer choice.
use Illuminate\Validation\Rule;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * The site-wide settings: how the site introduces itself, the defaults new
 * accounts start with, and whether registration is open.
 */
class SystemSettingsController extends Controller
{
    /**
     * Show the current settings.
     */
    public function edit(): View
    {
        return view('admin.system-settings', [
            // The saved settings, ready for the form inputs.
            'settings' => SystemSettings::values(),
            // The practice-timer options for the dropdown.
            'timerChoices' => StudyPreferences::TIME_LIMIT_CHOICES,
        ]);
    }

    /**
     * Save the site-wide settings.
     */
    public function update(Request $request): RedirectResponse
    {
        // Validate each setting; only validated keys are returned.
        $data = $request->validate([
            // Site name, up to 80 characters.
            'name' => ['required', 'string', 'max:80'],
            // Support email, must be a valid address.
            'support_email' => ['required', 'string', 'email', 'max:150'],
            // Optional support phone.
            'support_phone' => ['nullable', 'string', 'max:40'],
            // Optional support hours.
            'support_hours' => ['nullable', 'string', 'max:80'],
            // Default daily target, within the allowed range.
            'default_daily_target_items' => ['required', 'integer', 'min:'.SystemSettings::DAILY_TARGET_MIN, 'max:'.SystemSettings::DAILY_TARGET_MAX],
            // Default practice timer, must be a known choice.
            'default_practice_timer' => ['required', Rule::in(StudyPreferences::timeLimitChoices())],
            // Mock exam time limit in minutes, within the allowed range.
            'mock_time_limit_minutes' => ['required', 'integer', 'min:'.SystemSettings::MOCK_TIME_LIMIT_MIN, 'max:'.SystemSettings::MOCK_TIME_LIMIT_MAX],
        ]);

        // Save the settings; the registration checkbox is merged in as a boolean.
        SystemSettings::save($data + ['registration_enabled' => $request->boolean('registration_enabled')]);

        return back()->with('status', 'The system settings have been saved.');
    }
}
