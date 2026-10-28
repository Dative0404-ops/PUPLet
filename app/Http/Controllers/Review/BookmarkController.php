<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a single question in the bank.
use App\Models\Question;
// Return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// The incoming HTTP request and its input.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Bookmarked questions, so difficult items can be revisited later.
 */
class BookmarkController extends Controller
{
    /**
     * Every question the student has saved.
     */
    public function index(Request $request): View
    {
        return view('review.bookmarks', [
            // This student's bookmarks, newest first, with each question and
            // its topic and subject fetched in the same query.
            'bookmarks' => $request->user()->questionBookmarks()
                ->with('question.topic.subject')
                ->latest()
                ->get()
                // Skip rows whose question has since been deleted.
                ->filter(fn ($bookmark) => $bookmark->question !== null)
                // Re-index the collection from zero.
                ->values(),
        ]);
    }

    /**
     * Save a question, or remove it when it is already saved.
     */
    public function toggle(Request $request, Question $question): RedirectResponse
    {
        $user = $request->user();
        // Fetch the question's topic and subject if not already loaded.
        $question->loadMissing('topic.subject');
        // 404 unless this student may see the question's subject.
        abort_unless($question->topic && $user->coversSubject($question->topic->subject), 404);

        // Look for an existing bookmark for this question and user.
        $existing = $user->questionBookmarks()->where('question_id', $question->id)->first();

        // When a bookmark already exists, the toggle removes it.
        if ($existing) {
            $existing->delete();

            return back()->with('status', 'Removed from your bookmarks.');
        }

        $user->questionBookmarks()->create(['question_id' => $question->id]);

        return back()->with('status', 'Saved to your bookmarks.');
    }
}
