<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a quiz (here, the mock exam record).
use App\Models\Quiz;
// Eloquent model for a student account.
use App\Models\User;
// Helper that builds attempts and reports mock readiness.
use App\Support\AttemptBuilder;
// Helper holding the mock exam blueprint and defaults.
use App\Support\MockExam;
// Helper exposing admin-configurable system settings.
use App\Support\SystemSettings;
// Return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// The incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * The timed mock examination covering every LET area.
 */
class MockExamController extends Controller
{
    /**
     * The briefing page, including how many items the bank still needs.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        // The mock exam record for this track.
        $quiz = $this->mockQuiz($user);

        return view('review.mock-exam', [
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
                ->completed()
                // Only attempts on this mock exam.
                ->where('quiz_id', $quiz->id)
                // Newest completed first.
                ->latest('completed_at')
                // At most five rows.
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Start the mock examination, or resume the one already open.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        // The mock exam record for this track.
        $quiz = $this->mockQuiz($user);

        // Resume an attempt that is already open, if there is one.
        if ($open = $this->openAttempt($request, $quiz)) {
            return redirect()->route('review.attempt', $open);
        }

        // Otherwise build a new mock attempt.
        $attempt = AttemptBuilder::forMock($user, $quiz);

        // A null result means the bank lacks enough questions.
        if ($attempt === null) {
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
    private function mockQuiz(User $user): Quiz
    {
        // Find the mock quiz by type and track title, creating it with default
        // size and time limit when it does not exist yet.
        return Quiz::firstOrCreate(
            ['type' => Quiz::TYPE_MOCK, 'title' => MockExam::title($user->exam_type)],
            [
                'item_count' => MockExam::totalItems($user->exam_type),
                'time_limit_minutes' => SystemSettings::mockTimeLimitMinutes(),
            ],
        );
    }

    /**
     * The mock attempt the student left open, if any.
     */
    private function openAttempt(Request $request, Quiz $quiz)
    {
        // The student's attempts on the mock quiz, most recently started first.
        return $request->user()->quizAttempts()
            ->inProgress()
            ->where('quiz_id', $quiz->id)
            ->latest('started_at')
            // Return the first match, or null when none exists.
            ->first();
    }
}
