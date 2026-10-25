<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a single question in the bank.
use App\Models\Question;
// Return type for actions that send the browser somewhere else.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request and its input.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Bookmarked questions, so difficult items can be revisited later.
 */
// Controller for the student's saved (bookmarked) questions.
class BookmarkController extends Controller
{
    /**
     * Every question the student has saved.
     */
    // Lists the student's bookmarks on a dedicated page.
    public function index(Request $request): View
    {
        // Render the bookmarks page with the user's saved questions.
        return view('review.bookmarks', [
            // Load bookmarks, newest first, with question/topic/subject preloaded.
            'bookmarks' => $request->user()->questionBookmarks()
                // Eager-load the question and its topic and subject.
                ->with('question.topic.subject')
                // Order by creation time, newest first.
                ->latest()
                // Run the query.
                ->get()
                // Skip rows whose question has since been deleted.
                ->filter(fn ($bookmark) => $bookmark->question !== null)
                // Re-index the filtered collection from zero.
                ->values(),
        ]);
    }

    /**
     * Save a question, or remove it when it is already saved.
     */
    // Toggles a bookmark on or off (used by the AJAX button in the quiz view).
    public function toggle(Request $request, Question $question): RedirectResponse
    {
        // The signed-in student performing the toggle.
        $user = $request->user();
        // Load the question's topic and subject if not already present.
        $question->loadMissing('topic.subject');
        // 404 unless this student is allowed to see the question's subject.
        abort_unless($question->topic && $user->coversSubject($question->topic->subject), 404);

        // Look for an existing bookmark for this question and user.
        $existing = $user->questionBookmarks()->where('question_id', $question->id)->first();

        // When a bookmark already exists, the toggle removes it.
        if ($existing) {
            // Delete the saved bookmark row.
            $existing->delete();

            // Go back to the previous page with a confirmation message.
            return back()->with('status', 'Removed from your bookmarks.');
        }

        // Otherwise create a new bookmark row for this question.
        $user->questionBookmarks()->create(['question_id' => $question->id]);

        // Go back to the previous page confirming the save.
        return back()->with('status', 'Saved to your bookmarks.');
    }
}
