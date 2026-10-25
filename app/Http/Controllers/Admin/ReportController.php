<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The QuestionReport model these actions read and close.
use App\Models\QuestionReport;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and signed-in user).
use Illuminate\Http\Request;
// Rule is imported but rules are declared inline below.
use Illuminate\Validation\Rule;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * The triage queue for problems students have reported about questions.
 */
// Admins handle student reports about individual questions here.
class ReportController extends Controller
{
    /**
     * How many reports are listed on one page.
     */
    // Class-level constant: the page size used by the paginator in index().
    private const PER_PAGE = 20;

    /**
     * The report statuses an administrator can filter by.
     *
     * @var array<string, string>
     */
    // Reuse the QuestionReport status labels as the available filters.
    private const FILTERS = QuestionReport::STATUS_LABELS;

    /**
     * List the reports, newest first.
     */
    // Route action for GET /admin/reports.
    public function index(Request $request): View
    {
        // Read the requested status filter from the query string.
        $status = $request->string('status')->toString();

        // Ignore an unknown status value by resetting it to an empty string.
        if (! array_key_exists($status, self::FILTERS)) {
            // Empty string means "no status filter" in the query below.
            $status = '';
        }

        // Start building the query for the list of reports.
        $reports = QuestionReport::query()
            // Eager-load the question chain plus the reporter and resolver users.
            ->with(['question.topic.subject', 'reporter', 'resolver'])
            // When a status is chosen, filter to that status.
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            // Newest reports first (highest id).
            ->latest('id')
            // Split into pages of PER_PAGE rows.
            ->paginate(self::PER_PAGE)
            // Keep the active filter in the pagination links.
            ->withQueryString();

        // Render the reports view with the paginator and summary counters.
        return view('admin.reports', [
            // The paginated reports to loop over.
            'reports' => $reports,
            // The active filter value, used to highlight the tab.
            'status' => $status,
            // The available filter options.
            'filters' => self::FILTERS,
            // Headline counters shown above the list.
            'summary' => [
                // Reports still waiting to be handled (query scope).
                'open' => QuestionReport::open()->count(),
                // Reports that were closed as resolved.
                'resolved' => QuestionReport::where('status', QuestionReport::STATUS_RESOLVED)->count(),
                // Reports that were closed as dismissed.
                'dismissed' => QuestionReport::where('status', QuestionReport::STATUS_DISMISSED)->count(),
            ],
        ]);
    }

    /**
     * Close a report as dealt with.
     */
    // Route action for marking a report resolved.
    public function resolve(Request $request, QuestionReport $report): RedirectResponse
    {
        // Delegate to the shared close() helper with the "resolved" outcome.
        return $this->close($request, $report, QuestionReport::STATUS_RESOLVED, 'Report marked as resolved.');
    }

    /**
     * Close a report as needing no change.
     */
    // Route action for dismissing a report.
    public function dismiss(Request $request, QuestionReport $report): RedirectResponse
    {
        // Delegate to the shared close() helper with the "dismissed" outcome.
        return $this->close($request, $report, QuestionReport::STATUS_DISMISSED, 'Report dismissed.');
    }

    /**
     * Record the outcome of a report and close it.
     */
    // Shared logic used by resolve() and dismiss().
    private function close(Request $request, QuestionReport $report, string $status, string $message): RedirectResponse
    {
        // Validate the optional resolution note.
        $data = $request->validate([
            // The note may be empty and is capped at 500 characters.
            'resolution_note' => ['nullable', 'string', 'max:500'],
        ]);

        // Guard against closing a report that was already closed.
        if (! $report->isOpen()) {
            // Tell the admin it was already handled and change nothing.
            return back()->with('status', 'That report has already been closed.');
        }

        // Record the outcome on the report; forceFill bypasses mass-assignment.
        $report->forceFill([
            // The new status (resolved or dismissed).
            'status' => $status,
            // The optional note, defaulting to null.
            'resolution_note' => $data['resolution_note'] ?? null,
            // The id of the administrator who closed it.
            'resolved_by' => $request->user()->id,
            // The moment it was closed.
            'resolved_at' => now(),
        ])->save();

        // Return to the previous page with the caller's message.
        return back()->with('status', $message);
    }
}
