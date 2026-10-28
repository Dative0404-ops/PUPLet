<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Shows a student the history of their past quiz attempts.
class HistoryController extends Controller
{
    /**
     * How many attempts are listed per page.
     */
    private const PER_PAGE = 15;

    /**
     * Show the student's quiz history.
     */
    public function index(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();
        // The optional "type" filter from the query string.
        $type = $this->filterType($request);

        // Start with this student's attempts.
        $attempts = $user->quizAttempts()
            // Also fetch the quiz, topic, and subject in the same query.
            ->with('quiz.topic.subject')
            // Filter by type only when a specific type was chosen ("all" skips it).
            ->when(
                $type !== 'all',
                fn ($query) => $query->whereHas('quiz', fn ($quiz) => $quiz->where('type', $type)),
            )
            // Newest attempts first.
            ->latest('started_at')
            // Split the results into pages.
            ->paginate(self::PER_PAGE)
            // Keep the query string on the pagination links.
            ->withQueryString();

        return view('account.history', [
            'attempts' => $attempts,
            // The active filter, used to highlight the chosen tab.
            'type' => $type,
            // Labels for each filter option.
            'filters' => [
                'all' => 'Everything',
                // Practice-quiz option, keyed by the Quiz constant.
                Quiz::TYPE_PRACTICE => 'Practice quizzes',
                // Mock-exam option.
                Quiz::TYPE_MOCK => 'Mock examinations',
            ],
            // The headline figures shown above the list.
            'summary' => $this->summary($user),
        ]);
    }

    /**
     * The requested quiz type, or "all" when the filter is missing or unknown.
     */
    private function filterType(Request $request): string
    {
        // The raw query value (null when missing).
        $type = $request->query('type');

        // Return it only if it is one of the two known types.
        return in_array($type, [Quiz::TYPE_PRACTICE, Quiz::TYPE_MOCK], true) ? $type : 'all';
    }

    /**
     * The headline figures shown above the history.
     *
     * @return array<string, int|null>
     */
    private function summary(User $user): array
    {
        // Query for this student's completed attempts. Cloned below to reuse it.
        $completed = $user->quizAttempts()->completed();

        // Total items and correct answers across completed attempts.
        $items = (int) (clone $completed)->sum('total_items');
        $correct = (int) (clone $completed)->sum('score');

        // Highest per-attempt percentage, or null when there are no attempts.
        $best = (clone $completed)
            // Only fetch the columns needed.
            ->get(['score', 'total_items'])
            // Turn each attempt into a rounded percentage (0 when it had no items).
            ->map(fn ($attempt) => $attempt->total_items > 0
                ? (int) round($attempt->score / $attempt->total_items * 100)
                : 0)
            // Take the largest.
            ->max();

        return [
            // Attempts the student has finished.
            'attempts' => (clone $completed)->count(),
            // Attempts still open.
            'open' => $user->quizAttempts()->inProgress()->count(),
            'items' => $items,
            // Overall accuracy, or null when nothing has been answered.
            'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
            'best' => $best,
        ];
    }
}
