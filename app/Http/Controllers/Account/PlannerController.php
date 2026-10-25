<?php

// Groups this controller with the other student "Account" controllers.
namespace App\Http\Controllers\Account;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The StudyGoal model this controller creates, updates, and deletes.
use App\Models\StudyGoal;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and signed-in user).
use Illuminate\Http\Request;
// Rule builds validation rules such as "in" for allowed values.
use Illuminate\Validation\Rule;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Manages the student's study planner goals.
class PlannerController extends Controller
{
    /**
     * Display the study planner.
     */
    // Route action for GET /account/planner.
    public function index(Request $request): View
    {
        // The signed-in student whose goals are shown.
        $user = $request->user();
        // Midnight today, used to compare each goal's target date.
        $today = now()->startOfDay();

        // Load all of the student's goals, earliest date/time first.
        $goals = $user->studyGoals()
            // Primary ordering: the goal's target date.
            ->orderBy('target_date')
            // Secondary ordering: the goal's target time (for review sessions).
            ->orderBy('target_time')
            // Run the query and collect a Collection of StudyGoal models.
            ->get();

        // Goals that are not yet completed (completed_at is null).
        $open = $goals->whereNull('completed_at');

        // The student's daily-target goal that falls on today, if one exists.
        $todayTarget = $goals
            // Only consider goals of the "daily target" type.
            ->where('type', StudyGoal::TYPE_DAILY_TARGET)
            // Match the first daily target whose date is the same day as today.
            ->first(fn (StudyGoal $goal) => $goal->target_date->isSameDay($today));

        // Render the planner view, splitting the goals into meaningful groups.
        return view('account.planner', [
            // Open goals dated today or later, reindexed with values().
            'upcoming' => $open->filter(fn (StudyGoal $goal) => $goal->target_date->greaterThanOrEqualTo($today))->values(),
            // Open goals whose date has already passed.
            'overdue' => $open->filter(fn (StudyGoal $goal) => $goal->target_date->lessThan($today))->values(),
            // The ten most recently completed goals.
            'completed' => $goals
                // Keep only completed goals.
                ->whereNotNull('completed_at')
                // Newest completion first.
                ->sortByDesc('completed_at')
                // Cap the list at ten entries.
                ->take(10)
                // Reindex the collection.
                ->values(),
            // The daily-target goal for today (may be null).
            'todayTarget' => $todayTarget,
            // Items the student has answered today, for the progress bar.
            'itemsToday' => (int) $user->quizAttempts()
                // Only count submitted attempts.
                ->completed()
                // Only attempts completed today or later.
                ->where('completed_at', '>=', $today)
                // Sum the number of items on those attempts.
                ->sum('total_items'),
            // The student's preferred daily item target.
            'preferredTarget' => $user->daily_target_items,
        ]);
    }

    /**
     * Add a study goal to the planner.
     */
    // Route action for POST /account/planner.
    public function store(Request $request): RedirectResponse
    {
        // Create a goal through the student's relationship so user_id is set for them.
        $request->user()->studyGoals()->create($this->validated($request));

        // Send the student back to the planner with a success flash message.
        return redirect()
            // Named route for the planner page.
            ->route('account.planner')
            // Flash message shown once on the next request.
            ->with('status', 'Your study goal has been added.');
    }

    /**
     * Update a study goal in the planner.
     */
    // Route action for PUT/PATCH /account/planner/goals/{goal}.
    public function update(Request $request, StudyGoal $goal): RedirectResponse
    {
        // Ensure the signed-in student actually owns this goal.
        $this->authorizeOwner($request, $goal);

        // Apply the validated changes to the goal (mass assignment).
        $goal->update($this->validated($request));

        // Return to the planner with a success message.
        return redirect()
            // Named route for the planner page.
            ->route('account.planner')
            // Flash message for the next request.
            ->with('status', 'Your study goal has been updated.');
    }

    /**
     * Tick a goal off, or reopen it.
     */
    // Route action for toggling a goal's completed state.
    public function complete(Request $request, StudyGoal $goal): RedirectResponse
    {
        // Ensure the signed-in student owns this goal.
        $this->authorizeOwner($request, $goal);

        // Remember whether the goal was already completed before we toggle it.
        $wasCompleted = $goal->completed_at !== null;

        // Toggle: set completed_at to now, or back to null to reopen the goal.
        $goal->update(['completed_at' => $wasCompleted ? null : now()]);

        // Redirect back with a message reflecting which direction the toggle went.
        return redirect()
            // Named route for the planner page.
            ->route('account.planner')
            // Different flash message depending on whether it was reopened or done.
            ->with('status', $wasCompleted ? 'The goal has been reopened.' : 'The goal has been marked as done.');
    }

    /**
     * Remove a study goal from the planner.
     */
    // Route action for DELETE /account/planner/goals/{goal}.
    public function destroy(Request $request, StudyGoal $goal): RedirectResponse
    {
        // Ensure the signed-in student owns this goal.
        $this->authorizeOwner($request, $goal);

        // Delete the goal row from the database.
        $goal->delete();

        // Return to the planner with a confirmation message.
        return redirect()
            // Named route for the planner page.
            ->route('account.planner')
            // Flash message for the next request.
            ->with('status', 'The study goal has been removed.');
    }

    /**
     * Validate a goal from the planner form.
     *
     * @return array<string, mixed>
     */
    // Shared validation used by both store() and update().
    private function validated(Request $request): array
    {
        // Validate the request and return only the validated fields.
        return $request->validate([
            // Title is required, text, and at most 150 characters.
            'title' => ['required', 'string', 'max:150'],
            // Type must be one of the two known goal types.
            'type' => ['required', Rule::in([StudyGoal::TYPE_DAILY_TARGET, StudyGoal::TYPE_REVIEW_SESSION])],
            // Target date is required and must parse as a date.
            'target_date' => ['required', 'date'],
            // Time is optional for daily targets but required for review sessions.
            'target_time' => [
                // May be left empty (for daily targets).
                'nullable',
                // When present, must be formatted as HH:MM (24-hour).
                'date_format:H:i',
                // Required as soon as the goal type is a review session.
                'required_if:type,'.StudyGoal::TYPE_REVIEW_SESSION,
            ],
            // Item target is optional for review sessions but required for daily targets.
            'target_items' => [
                // May be left empty (for review sessions).
                'nullable',
                // When present, must be a whole number.
                'integer',
                // At least 1 item.
                'min:1',
                // At most 1000 items.
                'max:1000',
                // Required as soon as the goal type is a daily target.
                'required_if:type,'.StudyGoal::TYPE_DAILY_TARGET,
            ],
        ]);
    }

    /**
     * Stop a student from touching another student's goal.
     */
    // Ownership guard used by update, complete, and destroy.
    private function authorizeOwner(Request $request, StudyGoal $goal): void
    {
        // Return a 404 unless the goal's owner is the signed-in user.
        abort_unless($goal->user_id === $request->user()->id, 404);
    }
}
