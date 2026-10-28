<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\LetTrack;
use App\Support\StudyPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Lets a student view and edit their own profile.
class ProfileController extends Controller
{
    /**
     * Show the student's profile and the form that edits it.
     */
    public function edit(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();

        // Pass the current values and the option lists to the form.
        return view('account.profile', [
            'user' => $user,
            // The selectable LET exam types (elementary/secondary).
            'examTypes' => LetTrack::EXAM_TYPES,
            // The specialization options for each exam type.
            'specializations' => LetTrack::SPECIALIZATIONS,
            // Read-only counters summarising the student's activity.
            'summary' => [
                // Finished attempts and the items answered on them.
                'attempts' => $user->quizAttempts()->completed()->count(),
                'items' => (int) $user->quizAttempts()->completed()->sum('total_items'),
                // Lessons marked complete and questions bookmarked.
                'lessons' => $user->lessonProgresses()->whereNotNull('completed_at')->count(),
                'bookmarks' => $user->questionBookmarks()->count(),
                // Stored daily item target.
                'daily_target' => $user->daily_target_items,
                // Readable labels for the stored timer and landing-page values.
                'time_limit' => StudyPreferences::timeLimitLabel($user->practice_time_limit_minutes),
                'landing' => StudyPreferences::landingLabel($user->default_landing_page),
            ],
        ]);
    }

    /**
     * Update the student's name, email address, and LET track.
     */
    public function update(Request $request): RedirectResponse
    {
        // The signed-in student.
        $user = $request->user();

        // Validate the profile fields. Only validated keys are returned.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            // Email must be a unique, lowercase, well-formed address.
            'email' => [
                'required',
                'string',
                // Stored lowercased so lookups stay consistent.
                'lowercase',
                'email',
                'max:150',
                // Unique in users, ignoring this user's own row.
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            // Exam type must be one of the known LET exam types.
            'exam_type' => ['required', Rule::in(array_keys(LetTrack::EXAM_TYPES))],
            // Specialization must belong to the submitted exam type.
            'specialization' => [
                'required',
                'string',
                Rule::in(LetTrack::specializations($request->input('exam_type'))),
            ],
        ]);

        // Save the validated fields onto the user.
        $user->update($validated);

        // Go back to the profile page with a success message.
        return redirect()
            ->route('account.profile')
            ->with('status', 'Your profile has been updated.');
    }
}
