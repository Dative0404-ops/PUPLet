<?php

// Groups this controller with the other student "Account" controllers.
namespace App\Http\Controllers\Account;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The Quiz model, used here for its TYPE_* constants and Eloquent relationships.
use App\Models\Quiz;
// The User model, type-hinted in helper methods for clarity/static analysis.
use App\Models\User;
// Request is the incoming HTTP request (query string and signed-in user).
use Illuminate\Http\Request;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

// Shows a student the history of their past quiz attempts.
class HistoryController extends Controller
{
    /**
     * How many attempts are listed per page.
     */
    // Class-level constant: the page size used by the paginator below.
    private const PER_PAGE = 15;

    /**
     * Display the student's quiz history.
     */
    // Route action for GET /account/history.
    public function index(Request $request): View
    {
        // The signed-in student, reused throughout this method.
        $user = $request->user();
        // Read and normalise the optional "type" filter from the query string.
        $type = $this->filterType($request);

        // Start building the list of this student's quiz attempts.
        $attempts = $user->quizAttempts()
            // Eager-load quiz -> topic -> subject so the view runs no extra queries.
            ->with('quiz.topic.subject')
            // Apply the type filter only when a specific type was chosen ("all" skips it).
            ->when(
                // Condition: only filter when the user picked a concrete type.
                $type !== 'all',
                // Narrow to attempts whose related quiz has the chosen type.
                fn ($query) => $query->whereHas('quiz', fn ($quiz) => $quiz->where('type', $type)),
            )
            // Newest attempts first, ordered by the start time.
            ->latest('started_at')
            // Split the result set into pages of PER_PAGE rows.
            ->paginate(self::PER_PAGE)
            // Keep the active query string on the pagination links.
            ->withQueryString();

        // Render the history view, passing the paginator and helper data.
        return view('account.history', [
            // The paginated attempts the Blade template will loop over.
            'attempts' => $attempts,
            // The active filter, used to highlight the chosen tab.
            'type' => $type,
            // Human-readable labels for each filter option shown in the UI.
            'filters' => [
                // Option used when no type filter is applied.
                'all' => 'Everything',
                // Practice-quiz option, keyed by the Quiz model constant.
                Quiz::TYPE_PRACTICE => 'Practice quizzes',
                // Mock-exam option, keyed by the Quiz model constant.
                Quiz::TYPE_MOCK => 'Mock examinations',
            ],
            // The headline figures displayed above the list.
            'summary' => $this->summary($user),
        ]);
    }

    /**
     * The requested quiz type, or "all" when the filter is missing or unknown.
     */
    // Reads the "type" query parameter and falls back to "all".
    private function filterType(Request $request): string
    {
        // Pull the raw query-string value (null when the parameter is absent).
        $type = $request->query('type');

        // Accept the value only if it is one of the two known quiz types.
        return in_array($type, [Quiz::TYPE_PRACTICE, Quiz::TYPE_MOCK], true) ? $type : 'all';
    }

    /**
     * The headline figures shown above the history.
     *
     * @return array<string, int|null>
     */
    // Builds the summary counters for one student.
    private function summary(User $user): array
    {
        // A reusable query limited to completed attempts (a local scope).
        $completed = $user->quizAttempts()->completed();

        // Total items across completed attempts; clone keeps the base query reusable.
        $items = (int) (clone $completed)->sum('total_items');
        // Total correct answers across the same attempts.
        $correct = (int) (clone $completed)->sum('score');

        // Highest per-attempt percentage score, or null when there are no attempts.
        $best = (clone $completed)
            // Fetch only the columns needed to compute the percentage.
            ->get(['score', 'total_items'])
            // Turn each attempt into a rounded percentage (0 when it had no items).
            ->map(fn ($attempt) => $attempt->total_items > 0
                ? (int) round($attempt->score / $attempt->total_items * 100)
                : 0)
            // Take the largest percentage found.
            ->max();

        // Assemble the summary array consumed by the view.
        return [
            // How many attempts the student has finished.
            'attempts' => (clone $completed)->count(),
            // How many attempts are still open (not yet completed).
            'open' => $user->quizAttempts()->inProgress()->count(),
            // Total number of items answered.
            'items' => $items,
            // Overall accuracy percentage, or null when nothing has been answered.
            'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
            // Best single-attempt percentage (see above).
            'best' => $best,
        ];
    }
}
