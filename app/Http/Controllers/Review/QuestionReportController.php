<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a single question in the bank.
use App\Models\Question;
// Eloquent model for a student's report about a question.
use App\Models\QuestionReport;
// Return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// The incoming HTTP request and its input.
use Illuminate\Http\Request;
// Validation helper providing the "value in a fixed list" rule.
use Illuminate\Validation\Rule;

/**
 * Lets a student flag a question that looks wrong or is hard to understand.
 */
class QuestionReportController extends Controller
{
    /**
     * File a report about one question.
     */
    public function store(Request $request, Question $question): RedirectResponse
    {
        $user = $request->user();
        // Fetch the question's topic and subject if not already loaded.
        $question->loadMissing('topic.subject');
        // 404 unless the question is valid and inside the student's track.
        abort_unless($question->topic && $user->coversSubject($question->topic->subject), 404);

        // Check the report reason and optional details.
        $data = $request->validate([
            // The reason must be one of the known reason codes.
            'reason' => ['required', 'string', Rule::in(array_keys(QuestionReport::REASON_LABELS))],
            // Optional free-text details, capped in length.
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        // Whether this student already has an open report on this question.
        $alreadyOpen = $user->questionReports()
            ->where('question_id', $question->id)
            // Only reports still open (not yet resolved).
            ->open()
            ->exists();

        // If a report is already open, do not create a duplicate.
        if ($alreadyOpen) {
            return back()->with('status', 'You have already reported this question. Our team is looking into it.');
        }

        // Create the report row for this student and question.
        $user->questionReports()->create([
            'question_id' => $question->id,
            // The chosen reason code.
            'reason' => $data['reason'],
            // The optional details text, defaulting to null.
            'details' => $data['details'] ?? null,
            // New reports begin in the open state.
            'status' => QuestionReport::STATUS_OPEN,
        ]);

        return back()->with('status', 'Thank you. Your report has been sent to the reviewer team.');
    }
}
