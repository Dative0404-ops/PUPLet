<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// DssSettings holds the tunable rules behind the study recommendations.
use App\Support\DssSettings;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and signed-in user).
use Illuminate\Http\Request;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * The rules the decision support recommendations follow.
 */
// Admins edit the decision-support (recommendation) rules here.
class DssSettingsController extends Controller
{
    /**
     * The recommendation rules currently in force.
     */
    // Route action for GET /admin/dss-settings.
    public function edit(): View
    {
        // Render the settings form pre-filled with the current rule values.
        return view('admin.dss-settings', [
            // The stored rules, formatted for the form inputs.
            'settings' => DssSettings::values(),
        ]);
    }

    /**
     * Save the recommendation rules.
     */
    // Route action for PUT/PATCH /admin/dss-settings.
    public function update(Request $request): RedirectResponse
    {
        // Validate the three numeric rules using each rule's allowed range.
        $data = $request->validate([
            // Minimum answered items before a topic is scored, within its range.
            'min_items' => ['required', 'integer', 'min:'.DssSettings::MIN_ITEMS_MIN, 'max:'.DssSettings::MIN_ITEMS_MAX],
            // Number of topics in the recommendation list, within its range.
            'limit' => ['required', 'integer', 'min:'.DssSettings::LIMIT_MIN, 'max:'.DssSettings::LIMIT_MAX],
            // Accuracy percentage below which a topic counts as weak, within its range.
            'weak_threshold' => ['required', 'integer', 'min:'.DssSettings::THRESHOLD_MIN, 'max:'.DssSettings::THRESHOLD_MAX],
        ]);

        // Persist the rules; the checkbox is read as a boolean flag and merged in.
        DssSettings::save($data + ['include_untouched' => $request->boolean('include_untouched')]);

        // Return to the previous page with a success message.
        return back()->with('status', 'The recommendation rules have been saved.');
    }
}
