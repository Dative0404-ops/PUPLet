<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// Holds the tunable rules behind the study recommendations.
use App\Support\DssSettings;
// Returned when an action redirects the browser.
use Illuminate\Http\RedirectResponse;
// The current request: form input and signed-in user.
use Illuminate\Http\Request;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * The rules behind the study recommendations.
 */
class DssSettingsController extends Controller
{
    /**
     * Show the current recommendation rules.
     */
    public function edit(): View
    {
        return view('admin.dss-settings', [
            // The saved rules, ready for the form inputs.
            'settings' => DssSettings::values(),
        ]);
    }

    /**
     * Save the recommendation rules.
     */
    public function update(Request $request): RedirectResponse
    {
        // Validate the three numeric rules, each within its allowed range.
        $data = $request->validate([
            // Fewest answered items needed before a topic is scored.
            'min_items' => ['required', 'integer', 'min:'.DssSettings::MIN_ITEMS_MIN, 'max:'.DssSettings::MIN_ITEMS_MAX],
            // How many topics the recommendation list shows.
            'limit' => ['required', 'integer', 'min:'.DssSettings::LIMIT_MIN, 'max:'.DssSettings::LIMIT_MAX],
            // Accuracy percent below which a topic counts as weak.
            'weak_threshold' => ['required', 'integer', 'min:'.DssSettings::THRESHOLD_MIN, 'max:'.DssSettings::THRESHOLD_MAX],
        ]);

        // Save the rules; the checkbox is read as a boolean and merged in.
        DssSettings::save($data + ['include_untouched' => $request->boolean('include_untouched')]);

        return back()->with('status', 'The recommendation rules have been saved.');
    }
}
