<?php

// This class lives in the Review sub-namespace of the app's controllers.
namespace App\Http\Controllers\Review;

// Parent controller that every controller in the app extends.
use App\Http\Controllers\Controller;
// Eloquent model for a question a student has bookmarked.
use App\Models\QuestionBookmark;
// Eloquent model for one quiz attempt (a single sitting of a quiz).
use App\Models\QuizAttempt;
// Eloquent model for a single answer row inside an attempt.
use App\Models\QuizAttemptAnswer;
// Subject model; used here mainly for its AREA_LABELS map.
use App\Models\Subject;
// Support helper that builds a new attempt from a set of questions.
use App\Support\AttemptBuilder;
// Support helper that grades and finalises an attempt.
use App\Support\AttemptGrader;
// Return type used when an action redirects the browser elsewhere.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request and its submitted input.
use Illuminate\Http\Request;
// Generic in-memory collection returned by Eloquent queries.
use Illuminate\Support\Collection;
// Return type used when an action renders a Blade template.
use Illuminate\View\View;

/**
 * Taking a quiz: the questions, the results, and the mistake drills.
 */
// Controller grouping all student-facing quiz attempt actions.
class AttemptController extends Controller
{
    /**
     * Show the questions, or push the student along when the attempt is closed.
     */
    // Renders the quiz screen, or redirects when the attempt cannot be answered.
    public function show(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        // Safety check: only the owner is allowed to open this attempt.
        $this->authorizeOwner($request, $attempt);
        // Eager-load quiz, topic, and subject so the view avoids extra queries.
        $attempt->load('quiz.topic.subject');

        // A finished attempt has no questions left to answer.
        if (! $attempt->isInProgress()) {
            // Send the student straight to its results page.
            return redirect()->route('review.results', $attempt);
        }

        // If the countdown already expired, auto-submit instead of showing a quiz.
        if ($attempt->isOverdue()) {
            // Grade and close the attempt using the answers already saved.
            AttemptGrader::finish($attempt);

            // Go to results and explain why it was submitted automatically.
            return redirect()
                ->route('review.results', $attempt)
                ->with('status', 'Time was up, so your answers were submitted automatically.');
        }

        // Otherwise show the quiz screen together with its supporting data.
        return view('review.attempt', [
            // The attempt currently being answered.
            'attempt' => $attempt,
            // Answer rows in the order the student originally saw them.
            'answers' => $this->answers($attempt),
            // Question ids the student has bookmarked, for the bookmark toggle.
            'bookmarked' => $this->bookmarkedIds($request),
            // Seconds left, consumed by the client-side countdown timer.
            'remainingSeconds' => $attempt->remainingSeconds(),
        ]);
    }

    /**
     * Grade the submitted answers and move on to the results.
     */
    // Handles the form POST that finishes the attempt.
    public function submit(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        // Only the owner may submit this attempt.
        $this->authorizeOwner($request, $attempt);

        // Ignore a repeated submit when the attempt is already closed.
        if (! $attempt->isInProgress()) {
            // Simply show the existing results.
            return redirect()->route('review.results', $attempt);
        }

        // Validate the posted answers before trusting them.
        $validated = $request->validate([
            // The answers payload may be absent entirely (nothing answered).
            'answers' => ['nullable', 'array'],
            // Each entry maps a question position to a chosen option index.
            'answers.*' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        // Score the attempt with the submitted answers, defaulting to none.
        AttemptGrader::finish($attempt, $validated['answers'] ?? []);

        // Send the student on to the results page.
        return redirect()->route('review.results', $attempt);
    }

    /**
     * The score, percentage, and correct versus incorrect items.
     */
    // Shows the results summary page for a graded attempt.
    public function results(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        // Only the owner may view these results.
        $this->authorizeOwner($request, $attempt);
        // Load the related data the results view needs.
        $attempt->load('quiz.topic.subject');

        // An ongoing attempt has no results yet, so send the student back to it.
        if ($attempt->isInProgress()) {
            // Redirect to the live quiz screen.
            return redirect()->route('review.attempt', $attempt);
        }

        // Fetch the stored answer rows in display order.
        $answers = $this->answers($attempt);

        // Render the results page with its summary data.
        return view('review.results', [
            // The graded attempt itself.
            'attempt' => $attempt,
            // Every answer row, for the review list.
            'answers' => $answers,
            // Per-area correct counts used by the summary breakdown.
            'breakdown' => $this->breakdown($answers),
            // Bookmarked question ids for inline bookmark buttons.
            'bookmarked' => $this->bookmarkedIds($request),
        ]);
    }

    /**
     * Revisit only the items that were answered incorrectly.
     */
    // Shows the "drill your mistakes" page for a finished attempt.
    public function mistakes(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        // Only the owner may open the mistake drill.
        $this->authorizeOwner($request, $attempt);
        // Load just the quiz; the answers are queried separately below.
        $attempt->load('quiz');

        // Mistakes only exist once the attempt has been finished.
        if ($attempt->isInProgress()) {
            // Send unfinished attempts back to the live quiz.
            return redirect()->route('review.attempt', $attempt);
        }

        // Collect the incorrect answers, with their questions preloaded.
        $wrong = $attempt->answers()
            // Eager-load each question, its topic, and the subject.
            ->with('question.topic.subject')
            // Keep only answers marked wrong.
            ->where('is_correct', false)
            // Preserve the order the student originally saw them.
            ->orderBy('position')
            // Run the query.
            ->get();

        // Render the mistakes page.
        return view('review.mistakes', [
            // The attempt these wrong answers belong to.
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
    // Creates and opens a new attempt modelled on an earlier one.
    public function retry(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        // Only the owner may retry their own attempt.
        $this->authorizeOwner($request, $attempt);

        // The student who will own the new attempt.
        $user = $request->user();
        // Fetch the quiz this attempt referenced, failing if it is missing.
        $quiz = $attempt->quiz()->firstOrFail();
        // Read the retry mode from the form; "mistakes" narrows the question set.
        $onlyMistakes = $request->input('scope') === 'mistakes';

        // Build the list of questions to reuse in the new attempt.
        $questions = $attempt->answers()
            // Eager-load the related question models.
            ->with('question')
            // When drilling mistakes, keep only the incorrect answers.
            ->when($onlyMistakes, fn ($query) => $query->where('is_correct', false))
            // Keep the original presentation order.
            ->orderBy('position')
            // Execute the query.
            ->get()
            // Pull out just the question models.
            ->pluck('question')
            // Drop any nulls (for example, a question that was deleted).
            ->filter()
            // Re-index the collection from zero.
            ->values();

        // Ask the builder to create and persist the new attempt.
        $fresh = AttemptBuilder::fromQuestions($user, $quiz, $questions);

        // The builder returns null when there is nothing left to ask.
        if ($fresh === null) {
            // Return to the results page with an explanatory error.
            return redirect()
                ->route('review.results', $attempt)
                ->withErrors(['quiz' => 'There was nothing left to retry on this set.']);
        }

        // Open the fresh attempt with a status message that fits the mode.
        return redirect()
            ->route('review.attempt', $fresh)
            ->with('status', $onlyMistakes
                // Message when only the missed items are being retried.
                ? 'Retrying the '.$questions->count().' items you missed.'
                // Message when the entire set is being retried.
                : 'Retrying the whole set.');
    }

    /**
     * The attempt's answers, in the order the student saw them.
     *
     * @return Collection<int, QuizAttemptAnswer>
     */
    // Private helper: loads this attempt's answers with their question data.
    private function answers(QuizAttempt $attempt): Collection
    {
        // Query the attempt's answer rows.
        return $attempt->answers()
            // Eager-load question, topic, and subject for the view.
            ->with('question.topic.subject')
            // Keep the original question order.
            ->orderBy('position')
            // Run the query and return the collection.
            ->get();
    }

    /**
     * Correct answers per LET area, for the results summary.
     *
     * @param  Collection<int, QuizAttemptAnswer>  $answers
     * @return list<array<string, mixed>>
     */
    // Private helper: groups answers by LET area and computes per-area accuracy.
    private function breakdown(Collection $answers): array
    {
        // Group the answers by area, then map each group to summary numbers.
        return array_values($answers
            // Fall back to "other" when a question or its subject is missing.
            ->groupBy(fn (QuizAttemptAnswer $answer) => $answer->question?->topic?->subject?->area ?? 'other')
            // Convert each area group into a label/correct/total/percentage row.
            ->map(function (Collection $group, string $area) {
                // Count how many answers in this area were correct.
                $correct = $group->filter(fn (QuizAttemptAnswer $answer) => $answer->isCorrect())->count();

                // The summary row displayed for this area.
                return [
                    // Human-readable area name, with a safe fallback label.
                    'label' => Subject::AREA_LABELS[$area] ?? 'Other',
                    // Number of correct answers in the area.
                    'correct' => $correct,
                    // Total answers in the area.
                    'total' => $group->count(),
                    // Rounded percentage, guarding against divide-by-zero.
                    'percentage' => $group->count() > 0
                        ? (int) round($correct / $group->count() * 100)
                        : 0,
                ];
            })
            // Drop the area keys and return a plain, ordered list.
            ->all());
    }

    /**
     * The ids of every question the student has bookmarked.
     *
     * @return list<int>
     */
    // Private helper: fetches the bookmarked question ids for the current user.
    private function bookmarkedIds(Request $request): array
    {
        // Query the bookmark table for this user's rows.
        return QuestionBookmark::query()
            // Scope to the signed-in student.
            ->where('user_id', $request->user()->id)
            // Return only the question id column.
            ->pluck('question_id')
            // Convert the collection to a plain array.
            ->all();
    }

    /**
     * A student may only open their own attempts.
     */
    // Private helper: aborts with a 404 unless the attempt belongs to the user.
    private function authorizeOwner(Request $request, QuizAttempt $attempt): void
    {
        // Compare owner ids; a mismatch is hidden behind a 404 (not found).
        abort_unless($attempt->user_id === $request->user()->id, 404);
    }
}
