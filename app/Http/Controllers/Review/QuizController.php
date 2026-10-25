<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a quiz.
use App\Models\Quiz;
// Support helper that builds attempts and sizes question pools.
use App\Support\AttemptBuilder;
// Return type for actions that send the browser elsewhere.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Practice quizzes: a short, timed drill on one topic.
 */
// Controller for the per-topic practice quiz briefing and start.
class QuizController extends Controller
{
    /**
     * The quiz briefing page.
     */
    // Shows the briefing page for one quiz.
    public function show(Request $request, Quiz $quiz): View
    {
        // The signed-in student.
        $user = $request->user();
        // Load the quiz's topic and subject for the access check and view.
        $quiz->load('topic.subject');
        // Allow mocks always; otherwise require the track to cover the topic.
        abort_unless($quiz->isMock() || $user->coversSubject($quiz->topic->subject), 404);

        // Render the quiz briefing page.
        return view('review.quiz', [
            // The quiz being briefed.
            'quiz' => $quiz,
            // How many questions are currently available for this quiz.
            'available' => AttemptBuilder::poolForQuiz($quiz)->count(),
            // The time limit that will apply to this student and quiz.
            'timeLimit' => AttemptBuilder::timeLimitFor($user, $quiz),
            // An attempt left open, so it can be resumed, if any.
            'inProgress' => $this->openAttempt($request, $quiz),
            // The student's last few submitted attempts on this quiz.
            'pastAttempts' => $this->pastAttempts($request, $quiz),
        ]);
    }

    /**
     * Start a new attempt, or resume the one already open.
     */
    // Handles starting (or resuming) a practice quiz.
    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        // The signed-in student.
        $user = $request->user();
        // Load the quiz's topic and subject for the access check.
        $quiz->load('topic.subject');
        // Allow mocks always; otherwise require the track to cover the topic.
        abort_unless($quiz->isMock() || $user->coversSubject($quiz->topic->subject), 404);

        // Resume an attempt that is already open, if there is one.
        if ($open = $this->openAttempt($request, $quiz)) {
            // Send the student back into the open attempt.
            return redirect()->route('review.attempt', $open);
        }

        // Otherwise build a new practice attempt.
        $attempt = AttemptBuilder::forPractice($user, $quiz);

        // A null attempt means the quiz has no questions yet.
        if ($attempt === null) {
            // Return to the briefing page with an error message.
            return back()->withErrors([
                'quiz' => 'This practice quiz has no questions yet. Please check back soon.',
            ]);
        }

        // Open the new attempt.
        return redirect()->route('review.attempt', $attempt);
    }

    /**
     * The attempt the student left open, if any.
     */
    // Private helper: returns the student's unfinished attempt on this quiz.
    private function openAttempt(Request $request, Quiz $quiz)
    {
        // Query this student's attempts on the quiz.
        return $request->user()->quizAttempts()
            // Only attempts still in progress.
            ->inProgress()
            // Only attempts on this quiz.
            ->where('quiz_id', $quiz->id)
            // Most recently started first.
            ->latest('started_at')
            // Return the first match, or null when none exists.
            ->first();
    }

    /**
     * The last few submitted attempts on this quiz.
     */
    // Private helper: returns the student's recent completed attempts here.
    private function pastAttempts(Request $request, Quiz $quiz)
    {
        // Query this student's attempts on the quiz.
        return $request->user()->quizAttempts()
            // Only finished attempts.
            ->completed()
            // Only attempts on this quiz.
            ->where('quiz_id', $quiz->id)
            // Newest completed first.
            ->latest('completed_at')
            // At most five rows.
            ->limit(5)
            // Run the query.
            ->get();
    }
}
