<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// The report model these actions read and close.
use App\Models\QuestionReport;
// Returned when an action redirects the browser.
use Illuminate\Http\RedirectResponse;
// The current request: form input and signed-in user.
use Illuminate\Http\Request;
// Imported but the rules below are written inline.
use Illuminate\Validation\Rule;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * Handle the reports students file about questions.
 */
class ReportController extends Controller
{
    /**
     * How many reports are listed per page.
     */
    private const PER_PAGE = 20;

    /**
     * The report statuses an administrator can filter by.
     *
     * @var array<string, string>
     */
    private const FILTERS = QuestionReport::STATUS_LABELS;

    /**
     * List the reports, newest first.
     */
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        // If the status is not a known filter, clear it (empty means "no filter").
        if (! array_key_exists($status, self::FILTERS)) {
            $status = '';
        }

        // Get the reports; also fetch the question chain, reporter, and resolver.
        $reports = QuestionReport::query()
            ->with(['question.topic.subject', 'reporter', 'resolver'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.reports', [
            'reports' => $reports,
            // The active filter, used to highlight its tab.
            'status' => $status,
            'filters' => self::FILTERS,
            // Headline counters shown above the list.
            'summary' => [
                'open' => QuestionReport::open()->count(),
                'resolved' => QuestionReport::where('status', QuestionReport::STATUS_RESOLVED)->count(),
                'dismissed' => QuestionReport::where('status', QuestionReport::STATUS_DISMISSED)->count(),
            ],
        ]);
    }

    /**
     * Close a report as dealt with.
     */
    public function resolve(Request $request, QuestionReport $report): RedirectResponse
    {
        return $this->close($request, $report, QuestionReport::STATUS_RESOLVED, 'Report marked as resolved.');
    }

    /**
     * Close a report as needing no change.
     */
    public function dismiss(Request $request, QuestionReport $report): RedirectResponse
    {
        return $this->close($request, $report, QuestionReport::STATUS_DISMISSED, 'Report dismissed.');
    }

    /**
     * Record the outcome of a report and close it.
     */
    private function close(Request $request, QuestionReport $report, string $status, string $message): RedirectResponse
    {
        $data = $request->validate([
            // Optional note, up to 500 characters.
            'resolution_note' => ['nullable', 'string', 'max:500'],
        ]);

        // Do nothing if the report was already closed.
        if (! $report->isOpen()) {
            return back()->with('status', 'That report has already been closed.');
        }

        // Save the outcome. forceFill skips mass-assignment guarding.
        $report->forceFill([
            // The new status: resolved or dismissed.
            'status' => $status,
            // The optional note, null when omitted.
            'resolution_note' => $data['resolution_note'] ?? null,
            // The admin who closed it, and when.
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ])->save();

        return back()->with('status', $message);
    }
}
