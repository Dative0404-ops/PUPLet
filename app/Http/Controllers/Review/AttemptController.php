<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller in the app extends.
use App\Http\Controllers\Controller;
// Eloquent model for a question a student has bookmarked.
use App\Models\QuestionBookmark;
// Eloquent model for one quiz attempt (a single sitting of a quiz).
use App\Models\QuizAttempt;
// Eloquent model for a single answer row inside an attempt.
use App\Models\QuizAttemptAnswer;
// Subject model; used here for its AREA_LABELS map.
use App\Models\Subject;
// Helper that builds a new attempt from a set of questions.
use App\Support\AttemptBuilder;
// Helper that grades and finishes an attempt.
use App\Support\AttemptGrader;
// Return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// The incoming HTTP request and its submitted input.
use Illuminate\Http\Request;
// In-memory collection returned by Eloquent queries.
use Illuminate\Support\Collection;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Taking a quiz: the questions, the results, and the mistake drills.
 */
class AttemptController extends Controller
{
    /**
     * Show the quiz questions, or redirect when the attempt is already closed.
     */
    public function show(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        // Only the owner may open this attempt.
        $this->authorizeOwner($request, $attempt);
        // Also fetch quiz, topic, and subject in the same query.
        $attempt->load('quiz.topic.subject');

        // A finished attempt has no questions left, so show its results.
        if (! $attempt->isInProgress()) {
            return redirect()->route('review.results', $attempt);
        }

        // If the time already ran out, grade and close the attempt now.
        if ($attempt->isOverdue()) {
            AttemptGrader::finish($attempt);

            // Explain why it was submitted automatically.
            return redirect()
                ->route('review.results', $attempt)
                ->with('status', 'Time was up, so your answers were submitted automatically.');
        }

        return view('review.attempt', [
            'attempt' => $attempt,
            // Answer rows in the order the student saw them.
            'answers' => $this->answers($attempt),
            // Question ids the student has bookmarked, for the bookmark toggle.
            'bookmarked' => $this->bookmarkedIds($request),
            // Seconds left, used by the countdown timer in the browser.
            'remainingSeconds' => $attempt->remainingSeconds(),
        ]);
    }

    /**
     * Grade the submitted answers and move on to the results.
     */
    public function submit(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        // Only the owner may submit this attempt.
        $this->authorizeOwner($request, $attempt);

        // Ignore a repeated submit when the attempt is already closed.
        if (! $attempt->isInProgress()) {
            return redirect()->route('review.results', $attempt);
        }

        // Check the posted answers before trusting them.
        $validated = $request->validate([
            // The answers may be absent entirely (nothing answered).
            'answers' => ['nullable', 'array'],
            // Each entry maps a question position to a chosen option index.
            'answers.*' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        // Score the attempt with the submitted answers, defaulting to none.
        AttemptGrader::finish($attempt, $validated['answers'] ?? []);

        return redirect()->route('review.results', $attempt);
    }

    /**
     * The score, percentage, and correct versus incorrect items.
     */
    public function results(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        // Only the owner may view these results.
        $this->authorizeOwner($request, $attempt);
        // Also fetch quiz, topic, and subject for the view.
        $attempt->load('quiz.topic.subject');

        // An ongoing attempt has no results yet, so send the student back to it.
        if ($attempt->isInProgress()) {
            return redirect()->route('review.attempt', $attempt);
        }

        // The stored answer rows, in display order.
        $answers = $this->answers($attempt);

        return view('review.results', [
            'attempt' => $attempt,
            'answers' => $answers,
            // Per-area correct counts used by the summary breakdown.
            'breakdown' => $this->breakdown($answers),
            // Bookmarked question ids for the inline bookmark buttons.
            'bookmarked' => $this->bookmarkedIds($request),
        ]);
    }

    /**
     * Revisit only the items that were answered incorrectly.
     */
    public function mistakes(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        // Only the owner may open the mistake drill.
        $this->authorizeOwner($request, $attempt);
        // Fetch just the quiz; the answers are queried below.
        $attempt->load('quiz');

        // Mistakes only exist once the attempt has been finished.
        if ($attempt->isInProgress()) {
            return redirect()->route('review.attempt', $attempt);
        }

        // The incorrect answers, with each question, topic, and subject fetched
        // in the same query, still in the order the student saw them.
        $wrong = $attempt->answers()
            ->with('question.topic.subject')
            ->where('is_correct', false)
            ->orderBy('position')
            ->get();

        return view('review.mistakes', [
            'attempt' => $attempt,
            // The incorrect answer rows to review.
            'wrong' => $wrong,
            // Bookmarked question ids for the bookmark toggle.
            'bookmarked' => $this->bookmarkedIds($request),
        ]);
    }

    /**
     * Start a fresh attempt with the same questions, or only the missed ones.
     */
    public function retry(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        // Only the owner may retry their own attempt.
        $this->authorizeOwner($request, $attempt);

        $user = $request->user();
        // Find the quiz this attempt used, failing if it is missing.
        $quiz = $attempt->quiz()->firstOrFail();
        // Read the retry mode from the form; "mistakes" narrows the question set.
        $onlyMistakes = $request->input('scope') === 'mistakes';

        // The questions to reuse, still in the order the student saw them.
        $questions = $attempt->answers()
            ->with('question')
            // When drilling mistakes, keep only the incorrect answers.
            ->when($onlyMistakes, fn ($query) => $query->where('is_correct', false))
            ->orderBy('position')
            ->get()
            // Pull out just the question models.
            ->pluck('question')
            // Drop any nulls (for example, a question that was deleted).
            ->filter()
            // Re-index the collection from zero.
            ->values();

        // Create and save the new attempt.
        $fresh = AttemptBuilder::fromQuestions($user, $quiz, $questions);

        // The builder returns null when there is nothing left to ask.
        if ($fresh === null) {
            // Return to the results page with an error message.
            return redirect()
                ->route('review.results', $attempt)
                ->withErrors(['quiz' => 'There was nothing left to retry on this set.']);
        }

        // Open the new attempt with a message that fits the mode.
        return redirect()
            ->route('review.attempt', $fresh)
            ->with('status', $onlyMistakes
                // Message when only the missed items are retried.
                ? 'Retrying the '.$questions->count().' items you missed.'
                : 'Retrying the whole set.');
    }

    /**
     * The attempt's answers, in the order the student saw them.
     *
     * @return Collection<int, QuizAttemptAnswer>
     */
    private function answers(QuizAttempt $attempt): Collection
    {
        // The answer rows, with each question, topic, and subject fetched in
        // the same query.
        return $attempt->answers()
            ->with('question.topic.subject')
            ->orderBy('position')
            ->get();
    }

    /**
     * Correct answers per LET area, for the results summary.
     *
     * @param  Collection<int, QuizAttemptAnswer>  $answers
     * @return list<array<string, mixed>>
     */
    private function breakdown(Collection $answers): array
    {
        // Group the answers by LET area, then turn each group into a summary row.
        return array_values($answers
            // Fall back to "other" when a question or its subject is missing.
            ->groupBy(fn (QuizAttemptAnswer $answer) => $answer->question?->topic?->subject?->area ?? 'other')
            ->map(function (Collection $group, string $area) {
                // How many answers in this area were correct.
                $correct = $group->filter(fn (QuizAttemptAnswer $answer) => $answer->isCorrect())->count();

                return [
                    // Friendly area name, with a safe fallback label.
                    'label' => Subject::AREA_LABELS[$area] ?? 'Other',
                    'correct' => $correct,
                    'total' => $group->count(),
                    // Rounded percentage, guarding against divide-by-zero.
                    'percentage' => $group->count() > 0
                        ? (int) round($correct / $group->count() * 100)
                        : 0,
                ];
            })
            // Drop the area keys and return a plain list.
            ->all());
    }

    /**
     * The ids of every question the student has bookmarked.
     *
     * @return list<int>
     */
    private function bookmarkedIds(Request $request): array
    {
        // This student's bookmarked question ids.
        return QuestionBookmark::query()
            ->where('user_id', $request->user()->id)
            // Return only the question id column.
            ->pluck('question_id')
            ->all();
    }

    /**
     * A student may only open their own attempts.
     */
    private function authorizeOwner(Request $request, QuizAttempt $attempt): void
    {
        // Compare owner ids; a mismatch returns a 404 (not found).
        abort_unless($attempt->user_id === $request->user()->id, 404);
    }
}
