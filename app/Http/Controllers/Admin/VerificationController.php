<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The Question model being checked in the verification queue.
use App\Models\Question;
// Request is the incoming HTTP request (query string for filters).
use Illuminate\Http\Request;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * Content verification: the work queue of questions that still need a source,
 * an explanation, and a reviewer's sign-off.
 */
// Admins use this queue to find questions that still need attention.
class VerificationController extends Controller
{
    /**
     * How many questions are listed per page.
     */
    // Class-level constant: the page size used by the paginator in index().
    private const PER_PAGE = 20;

    /**
     * The states the verification queue can be filtered by.
     *
     * @var array<string, string>
     */
    // Maps each filter key to the label shown in the UI.
    private const FILTERS = [
        // Published questions that have not yet been verified.
        'unverified' => 'Published, not verified',
        // Published questions with no explanation text.
        'unexplained' => 'No explanation written',
        // Questions with no source recorded.
        'unsourced' => 'No source recorded',
        // Questions that have been verified.
        'verified' => 'Verified',
    ];

    /**
     * Show the questions that need attention, newest first.
     */
    // Route action for GET /admin/verification.
    public function index(Request $request): View
    {
        // Read the requested filter from the query string.
        $filter = $request->string('filter')->toString();

        // Fall back to the default filter when the value is unknown or missing.
        if (! isset(self::FILTERS[$filter])) {
            // "unverified" is the most useful starting point.
            $filter = 'unverified';
        }

        // Start building the query for the queue.
        $questions = Question::query()
            // Eager-load each question's topic chain and its reviewer.
            ->with(['topic.subject', 'reviewer'])
            // Filter: published questions that are not yet verified.
            ->when($filter === 'unverified', fn ($query) => $query->published()->whereNull('verified_at'))
            // Filter: questions that have been verified.
            ->when($filter === 'verified', fn ($query) => $query->whereNotNull('verified_at'))
            // Filter: questions with no source (null or empty string).
            ->when($filter === 'unsourced', fn ($query) => $query->where(
                // Group the two conditions so the OR stays scoped.
                fn ($inner) => $inner->whereNull('source')->orWhere('source', ''),
            ))
            // Filter: published questions with no explanation.
            ->when($filter === 'unexplained', fn ($query) => $query->published()->where(
                // Group the two conditions so the OR stays scoped.
                fn ($inner) => $inner->whereNull('explanation')->orWhere('explanation', ''),
            ))
            // Newest questions first (highest id).
            ->latest('id')
            // Split into pages of PER_PAGE rows.
            ->paginate(self::PER_PAGE)
            // Keep the active filter in the pagination links.
            ->withQueryString();

        // Render the verification queue with the paginator and counters.
        return view('admin.verification', [
            // The paginated questions to loop over.
            'questions' => $questions,
            // The active filter value, used to highlight the tab.
            'filter' => $filter,
            // The available filter options.
            'filters' => self::FILTERS,
            // Headline counters shown above the list.
            'summary' => [
                // Every question in the bank.
                'total' => Question::count(),
                // Questions with the published status (query scope).
                'published' => Question::published()->count(),
                // Questions that have been verified.
                'verified' => Question::whereNotNull('verified_at')->count(),
                // Questions with no source recorded.
                'unsourced' => Question::where(fn ($query) => $query->whereNull('source')->orWhere('source', ''))->count(),
                // Published questions with no explanation.
                'unexplained' => Question::published()
                    // Group the two conditions so the OR stays scoped.
                    ->where(fn ($query) => $query->whereNull('explanation')->orWhere('explanation', ''))
                    // Count the matching rows.
                    ->count(),
            ],
        ]);
    }
}
