<?php

// Groups this controller with the other student "Account" controllers.
namespace App\Http\Controllers\Account;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// LetTrack holds the LET exam types and their specialization options.
use App\Support\LetTrack;
// StudyPreferences turns stored preference values into readable labels.
use App\Support\StudyPreferences;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and signed-in user).
use Illuminate\Http\Request;
// Rule builds validation rules such as "in" and "unique".
use Illuminate\Validation\Rule;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Lets a student view and edit their own profile.
class ProfileController extends Controller
{
    /**
     * Display the student's profile and the form that edits it.
     */
    // Route action for GET /account/profile.
    public function edit(Request $request): View
    {
        // The signed-in student whose profile is being shown.
        $user = $request->user();

        // Render the profile form, passing the current values and option lists.
        return view('account.profile', [
            // The user being edited, supplied to pre-fill the form.
            'user' => $user,
            // The selectable LET exam types (elementary/secondary).
            'examTypes' => LetTrack::EXAM_TYPES,
            // The specialization options available per exam type.
            'specializations' => LetTrack::SPECIALIZATIONS,
            // Read-only counters summarising the student's activity.
            'summary' => [
                // Number of completed quiz attempts.
                'attempts' => $user->quizAttempts()->completed()->count(),
                // Total items answered across those completed attempts.
                'items' => (int) $user->quizAttempts()->completed()->sum('total_items'),
                // Lessons the student has marked complete.
                'lessons' => $user->lessonProgresses()->whereNotNull('completed_at')->count(),
                // Questions the student has bookmarked.
                'bookmarks' => $user->questionBookmarks()->count(),
                // The student's stored daily item target.
                'daily_target' => $user->daily_target_items,
                // Readable label for the stored practice timer value.
                'time_limit' => StudyPreferences::timeLimitLabel($user->practice_time_limit_minutes),
                // Readable label for the stored landing-page choice.
                'landing' => StudyPreferences::landingLabel($user->default_landing_page),
            ],
        ]);
    }

    /**
     * Update the student's name, email address, and LET track.
     */
    // Route action for PUT/PATCH /account/profile.
    public function update(Request $request): RedirectResponse
    {
        // The signed-in student whose profile is being updated.
        $user = $request->user();

        // Validate the submitted profile fields; only validated keys are returned.
        $validated = $request->validate([
            // Name is required, text, and at most 100 characters.
            'name' => ['required', 'string', 'max:100'],
            // Email must be a unique, lowercase, well-formed address.
            'email' => [
                // Must be present.
                'required',
                // Must be a string.
                'string',
                // Stored lowercased so lookups stay consistent.
                'lowercase',
                // Must look like an email address.
                'email',
                // At most 150 characters.
                'max:150',
                // Must be unique in the users table, ignoring this user's own row.
                Rule::unique('users', 'email')->ignore($user->id),
            ],
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
        ]);

        // Persist the validated fields onto the user via mass assignment.
        $user->update($validated);

        // Redirect back to the profile page with a flash status message.
        return redirect()
            // Named route for the profile page.
            ->route('account.profile')
            // Flash message kept in the session for the next request.
            ->with('status', 'Your profile has been updated.');
    }
}
