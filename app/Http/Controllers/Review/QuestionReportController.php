<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a single question in the bank.
use App\Models\Question;
// Eloquent model for a student's report about a question.
use App\Models\QuestionReport;
// Return type for actions that send the browser elsewhere.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request and its input.
use Illuminate\Http\Request;
// Validation helper providing the "value in a fixed list" rule.
use Illuminate\Validation\Rule;

/**
 * Lets a student flag a question that looks wrong or is hard to understand.
 */
// Controller for filing reports about individual questions.
class QuestionReportController extends Controller
{
    /**
     * File a report about one question.
     */
    // Handles the report form; creates a report row when valid.
    public function store(Request $request, Question $question): RedirectResponse
    {
        // The signed-in student.
        $user = $request->user();
        // Load the question's topic and subject if not already present.
        $question->loadMissing('topic.subject');
        // 404 unless the question is valid and inside the student's track.
        abort_unless($question->topic && $user->coversSubject($question->topic->subject), 404);

        // Validate the report reason and optional details.
        $data = $request->validate([
            // The reason must be one of the known reason codes.
            'reason' => ['required', 'string', Rule::in(array_keys(QuestionReport::REASON_LABELS))],
            // Optional free-text details, capped in length.
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        // Check whether this student already has an open report on the question.
        $alreadyOpen = $user->questionReports()
            // Scope to this question.
            ->where('question_id', $question->id)
            // Only reports still open (not yet resolved).
            ->open()
            // Return a boolean instead of the rows.
            ->exists();

        // If a report is already open, avoid creating a duplicate.
        if ($alreadyOpen) {
            // Go back with a status noting the report was already sent.
            return back()->with('status', 'You have already reported this question. Our team is looking into it.');
        }

        // Create the report row for this student and question.
        $user->questionReports()->create([
            // The question being reported.
            'question_id' => $question->id,
            // The chosen reason code.
            'reason' => $data['reason'],
            // The optional details text, defaulting to null.
            'details' => $data['details'] ?? null,
            // New reports begin in the open state.
            'status' => QuestionReport::STATUS_OPEN,
        ]);

        // Go back confirming the report was received.
        return back()->with('status', 'Thank you. Your report has been sent to the reviewer team.');
    }
}
