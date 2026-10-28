<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// The question model checked in this queue.
use App\Models\Question;
// The current request, used for its filter query string.
use Illuminate\Http\Request;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * The queue of questions that still need a source, an explanation,
 * or a reviewer's sign-off.
 */
class VerificationController extends Controller
{
    /**
     * How many questions are listed per page.
     */
    private const PER_PAGE = 20;

    /**
     * The states the verification queue can be filtered by.
     *
     * @var array<string, string>
     */
    private const FILTERS = [
        // Published but not yet verified.
        'unverified' => 'Published, not verified',
        // Published with no explanation.
        'unexplained' => 'No explanation written',
        // No source recorded.
        'unsourced' => 'No source recorded',
        // Already verified.
        'verified' => 'Verified',
    ];

    /**
     * Show the questions that need attention, newest first.
     */
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();

        // Fall back to "unverified" when the filter is unknown or missing.
        if (! isset(self::FILTERS[$filter])) {
            $filter = 'unverified';
        }

        // Get the questions for the chosen filter, newest first, 20 per page.
        $questions = Question::query()
            // Also fetch each question's topic, subject, and reviewer in the same query.
            ->with(['topic.subject', 'reviewer'])
            ->when($filter === 'unverified', fn ($query) => $query->published()->whereNull('verified_at'))
            ->when($filter === 'verified', fn ($query) => $query->whereNotNull('verified_at'))
            // A missing source can be null or an empty string, so check both.
            ->when($filter === 'unsourced', fn ($query) => $query->where(
                fn ($inner) => $inner->whereNull('source')->orWhere('source', ''),
            ))
            // A missing explanation can be null or an empty string, so check both.
            ->when($filter === 'unexplained', fn ($query) => $query->published()->where(
                fn ($inner) => $inner->whereNull('explanation')->orWhere('explanation', ''),
            ))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.verification', [
            'questions' => $questions,
            // The active filter, used to highlight its tab.
            'filter' => $filter,
            'filters' => self::FILTERS,
            // Headline counters shown above the list.
            'summary' => [
                'total' => Question::count(),
                'published' => Question::published()->count(),
                'verified' => Question::whereNotNull('verified_at')->count(),
                'unsourced' => Question::where(fn ($query) => $query->whereNull('source')->orWhere('source', ''))->count(),
                'unexplained' => Question::published()
                    ->where(fn ($query) => $query->whereNull('explanation')->orWhere('explanation', ''))
                    ->count(),
            ],
        ]);
    }
}
