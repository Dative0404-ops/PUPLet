<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a topic's reviewer material (a lesson).
use App\Models\Lesson;
// Eloquent model tracking a student's progress through a lesson.
use App\Models\LessonProgress;
// Return type for actions that send the browser elsewhere.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Reviewer materials: the reading part of the reviewer.
 */
// Controller for reading a lesson and marking it reviewed.
class LessonController extends Controller
{
    /**
     * Read a topic's reviewer material.
     */
    // Shows a lesson's reading material and records that it was opened.
    public function show(Request $request, Lesson $lesson): View
    {
        // The signed-in student.
        $user = $request->user();
        // Load the lesson's topic and subject for access checks and the view.
        $lesson->load('topic.subject');
        // 404 unless this student's track covers the lesson's subject.
        abort_unless($user->coversSubject($lesson->topic->subject), 404);

        // Fetch the student's progress row, or start a new, unsaved one.
        $progress = LessonProgress::firstOrNew([
            // The student this progress belongs to.
            'user_id' => $user->id,
            // The lesson being read.
            'lesson_id' => $lesson->id,
        ]);

        // Stamp the moment the lesson was last opened.
        $progress->last_viewed_at = now();

        // Opening a lesson counts as at least 10% progress.
        if ($progress->progress < 10) {
            // Raise the stored progress to that floor value.
            $progress->progress = 10;
        }

        // Persist the row (insert on first read, update thereafter).
        $progress->save();

        // Render the lesson reading page.
        return view('review.lesson', [
            // The lesson being read.
            'lesson' => $lesson,
            // The student's progress row.
            'progress' => $progress,
            // The topic's first quiz, offered after reading.
            'quiz' => $lesson->topic->quizzes()->first(),
        ]);
    }

    /**
     * Mark a topic's reviewer material as reviewed.
     */
    // Handles the action that marks a lesson fully reviewed.
    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        // The signed-in student.
        $user = $request->user();
        // Load the lesson's topic and subject for the access check.
        $lesson->load('topic.subject');
        // 404 unless this student's track covers the lesson's subject.
        abort_unless($user->coversSubject($lesson->topic->subject), 404);

        // Create or update the progress row as fully complete.
        LessonProgress::updateOrCreate(
            // Match on the student and the lesson.
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            // Set 100% progress and both relevant timestamps.
            ['progress' => 100, 'last_viewed_at' => now(), 'completed_at' => now()],
        );

        // Return to the lesson page with a confirmation message.
        return redirect()
            ->route('review.lesson', $lesson)
            ->with('status', 'Marked "'.$lesson->title.'" as reviewed.');
    }
}
