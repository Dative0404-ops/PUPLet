<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a quiz (here, the mock exam record).
use App\Models\Quiz;
// Eloquent model for a student account.
use App\Models\User;
// Support helper that builds attempts and reports mock readiness.
use App\Support\AttemptBuilder;
// Support class holding the mock exam blueprint and defaults.
use App\Support\MockExam;
// Support class exposing admin-configurable system settings.
use App\Support\SystemSettings;
// Return type for actions that send the browser elsewhere.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * The timed mock examination covering every LET area.
 */
// Controller for the full-length, timed mock exam.
class MockExamController extends Controller
{
    /**
     * The briefing page, including how many items the bank still needs.
     */
    // Shows the mock exam briefing page.
    public function show(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();
        // Find or create the mock exam record for this track.
        $quiz = $this->mockQuiz($user);

        // Render the briefing page with readiness details.
        return view('review.mock-exam', [
            // The mock exam quiz record.
            'quiz' => $quiz,
            // Question availability per LET area (the blueprint).
            'blueprint' => AttemptBuilder::mockAvailability($user),
            // Whether enough questions exist to run a full mock exam.
            'ready' => AttemptBuilder::mockIsReady($user),
            // Total items the mock exam should contain for this track.
            'totalItems' => MockExam::totalItems($user->exam_type),
            // The configured time limit, in minutes.
            'timeLimit' => SystemSettings::mockTimeLimitMinutes(),
            // An attempt left open, so it can be resumed, if any.
            'inProgress' => $this->openAttempt($request, $quiz),
            // The student's five most recent completed mock attempts.
            'pastAttempts' => $request->user()->quizAttempts()
                // Only finished attempts.
                ->completed()
                // Only attempts on this mock exam.
                ->where('quiz_id', $quiz->id)
                // Newest completed first.
                ->latest('completed_at')
                // At most five rows.
                ->limit(5)
                // Run the query.
                ->get(),
        ]);
    }

    /**
     * Start the mock examination, or resume the one already open.
     */
    // Handles starting (or resuming) the mock exam.
    public function store(Request $request): RedirectResponse
    {
        // The signed-in student.
        $user = $request->user();
        // Find or create the mock exam record for this track.
        $quiz = $this->mockQuiz($user);

        // Resume an attempt that is already open, if there is one.
        if ($open = $this->openAttempt($request, $quiz)) {
            // Send the student back into the open attempt.
            return redirect()->route('review.attempt', $open);
        }

        // Otherwise build a brand-new mock attempt.
        $attempt = AttemptBuilder::forMock($user, $quiz);

        // A null result means the bank lacks enough questions.
        if ($attempt === null) {
            // Return to the briefing page with an error message.
            return back()->withErrors([
                'quiz' => 'The question bank does not hold enough items for a full mock examination yet.',
            ]);
        }

        // Open the new attempt and state how long the student has.
        return redirect()
            ->route('review.attempt', $attempt)
            ->with('status', 'Good luck. You have '.SystemSettings::mockTimeLimitMinutes().' minutes.');
    }

    /**
     * The mock examination record that matches the student's track.
     */
    // Private helper: finds or creates the mock exam quiz row for a track.
    private function mockQuiz(User $user): Quiz
    {
        // Look up the mock quiz by type and track-specific title, creating it
        // with default size and time limit when it does not exist yet.
        return Quiz::firstOrCreate(
            // The unique attributes identifying this mock quiz.
            ['type' => Quiz::TYPE_MOCK, 'title' => MockExam::title($user->exam_type)],
            // Extra attributes applied only when creating a new row.
            [
                // Number of items the mock exam should contain.
                'item_count' => MockExam::totalItems($user->exam_type),
                // Default time limit, in minutes.
                'time_limit_minutes' => SystemSettings::mockTimeLimitMinutes(),
            ],
        );
    }

    /**
     * The mock attempt the student left open, if any.
     */
    // Private helper: returns the student's unfinished mock attempt, if any.
    private function openAttempt(Request $request, Quiz $quiz)
    {
        // Query this student's attempts on the mock quiz.
        return $request->user()->quizAttempts()
            // Only attempts still in progress.
            ->inProgress()
            // Only attempts on this mock exam.
            ->where('quiz_id', $quiz->id)
            // Most recently started first.
            ->latest('started_at')
            // Return the first match, or null when none exists.
            ->first();
    }
}
