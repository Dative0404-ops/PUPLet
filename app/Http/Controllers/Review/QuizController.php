<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a quiz.
use App\Models\Quiz;
// Helper that builds attempts and sizes question pools.
use App\Support\AttemptBuilder;
// Return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// The incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Practice quizzes: a short, timed drill on one topic.
 */
class QuizController extends Controller
{
    /**
     * The quiz briefing page.
     */
    public function show(Request $request, Quiz $quiz): View
    {
        $user = $request->user();
        // Fetch the quiz's topic and subject for the access check and the view.
        $quiz->load('topic.subject');
        // Mocks are always allowed; otherwise the track must cover the topic.
        abort_unless($quiz->isMock() || $user->coversSubject($quiz->topic->subject), 404);

        return view('review.quiz', [
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
    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        $user = $request->user();
        // Fetch the quiz's topic and subject for the access check.
        $quiz->load('topic.subject');
        // Mocks are always allowed; otherwise the track must cover the topic.
        abort_unless($quiz->isMock() || $user->coversSubject($quiz->topic->subject), 404);

        // Resume an attempt that is already open, if there is one.
        if ($open = $this->openAttempt($request, $quiz)) {
            return redirect()->route('review.attempt', $open);
        }

        // Otherwise build a new practice attempt.
        $attempt = AttemptBuilder::forPractice($user, $quiz);

        // A null attempt means the quiz has no questions yet.
        if ($attempt === null) {
            return back()->withErrors([
                'quiz' => 'This practice quiz has no questions yet. Please check back soon.',
            ]);
        }

        return redirect()->route('review.attempt', $attempt);
    }

    /**
     * The attempt the student left open, if any.
     */
    private function openAttempt(Request $request, Quiz $quiz)
    {
        // The student's attempts on this quiz, most recently started first.
        return $request->user()->quizAttempts()
            ->inProgress()
            ->where('quiz_id', $quiz->id)
            ->latest('started_at')
            // Return the first match, or null when none exists.
            ->first();
    }

    /**
     * The last few submitted attempts on this quiz.
     */
    private function pastAttempts(Request $request, Quiz $quiz)
    {
        // The student's finished attempts on this quiz, newest first.
        return $request->user()->quizAttempts()
            ->completed()
            ->where('quiz_id', $quiz->id)
            ->latest('completed_at')
            // At most five rows.
            ->limit(5)
            ->get();
    }
}
