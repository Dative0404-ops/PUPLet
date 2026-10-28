<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\StudyGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Manages the student's study planner goals.
class PlannerController extends Controller
{
    /**
     * Show the study planner.
     */
    public function index(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();
        // Midnight today, used to compare each goal's target date.
        $today = now()->startOfDay();

        // Load all of the student's goals, earliest first.
        $goals = $user->studyGoals()
            // Sort by target date, then by target time.
            ->orderBy('target_date')
            ->orderBy('target_time')
            ->get();

        // Goals that are not yet completed.
        $open = $goals->whereNull('completed_at');

        // The daily-target goal that falls on today, if one exists.
        $todayTarget = $goals
            // Only goals of the "daily target" type.
            ->where('type', StudyGoal::TYPE_DAILY_TARGET)
            // The first one whose date is today.
            ->first(fn (StudyGoal $goal) => $goal->target_date->isSameDay($today));

        return view('account.planner', [
            // Open goals dated today or later, reindexed with values().
            'upcoming' => $open->filter(fn (StudyGoal $goal) => $goal->target_date->greaterThanOrEqualTo($today))->values(),
            // Open goals whose date has already passed.
            'overdue' => $open->filter(fn (StudyGoal $goal) => $goal->target_date->lessThan($today))->values(),
            // The ten most recently completed goals.
            'completed' => $goals
                // Keep only completed goals, newest first.
                ->whereNotNull('completed_at')
                ->sortByDesc('completed_at')
                // Cap the list at ten entries.
                ->take(10)
                ->values(),
            // The daily-target goal for today (may be null).
            'todayTarget' => $todayTarget,
            // Items the student has answered today, for the progress bar.
            'itemsToday' => (int) $user->quizAttempts()
                // Only finished attempts completed today or later.
                ->completed()
                ->where('completed_at', '>=', $today)
                // Sum the item counts.
                ->sum('total_items'),
            // The student's preferred daily item target.
            'preferredTarget' => $user->daily_target_items,
        ]);
    }

    /**
     * Add a study goal to the planner.
     */
    public function store(Request $request): RedirectResponse
    {
        // Create through the relationship so the user_id is set for us.
        $request->user()->studyGoals()->create($this->validated($request));

        // Go back to the planner with a success message.
        return redirect()
            ->route('account.planner')
            ->with('status', 'Your study goal has been added.');
    }

    /**
     * Update a study goal in the planner.
     */
    public function update(Request $request, StudyGoal $goal): RedirectResponse
    {
        // Stop the student from editing another student's goal.
        $this->authorizeOwner($request, $goal);

        // Apply the validated changes to the goal.
        $goal->update($this->validated($request));

        return redirect()
            ->route('account.planner')
            ->with('status', 'Your study goal has been updated.');
    }

    /**
     * Tick a goal off, or reopen it.
     */
    public function complete(Request $request, StudyGoal $goal): RedirectResponse
    {
        $this->authorizeOwner($request, $goal);

        // Remember whether the goal was already completed before we toggle it.
        $wasCompleted = $goal->completed_at !== null;

        // Set completed_at to now, or back to null to reopen the goal.
        $goal->update(['completed_at' => $wasCompleted ? null : now()]);

        // Go back with a message matching the direction of the toggle.
        return redirect()
            ->route('account.planner')
            ->with('status', $wasCompleted ? 'The goal has been reopened.' : 'The goal has been marked as done.');
    }

    /**
     * Remove a study goal from the planner.
     */
    public function destroy(Request $request, StudyGoal $goal): RedirectResponse
    {
        $this->authorizeOwner($request, $goal);

        // Delete the goal row.
        $goal->delete();

        return redirect()
            ->route('account.planner')
            ->with('status', 'The study goal has been removed.');
    }

    /**
     * Validate a goal from the planner form.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            // Type must be one of the two known goal types.
            'type' => ['required', Rule::in([StudyGoal::TYPE_DAILY_TARGET, StudyGoal::TYPE_REVIEW_SESSION])],
            // Target date is required and must be a valid date.
            'target_date' => ['required', 'date'],
            // Time is optional for daily targets but required for review sessions.
            'target_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:type,'.StudyGoal::TYPE_REVIEW_SESSION,
            ],
            // Item target is optional for review sessions but required for daily targets.
            'target_items' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000',
                'required_if:type,'.StudyGoal::TYPE_DAILY_TARGET,
            ],
        ]);
    }

    /**
     * Stop a student from touching another student's goal.
     */
    private function authorizeOwner(Request $request, StudyGoal $goal): void
    {
        // Return 404 unless the goal belongs to the signed-in user.
        abort_unless($goal->user_id === $request->user()->id, 404);
    }
}
