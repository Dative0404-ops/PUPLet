<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a topic's reviewer material (a lesson).
use App\Models\Lesson;
// Eloquent model tracking a student's progress through a lesson.
use App\Models\LessonProgress;
// Return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// The incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Reviewer materials: the reading part of the reviewer.
 */
class LessonController extends Controller
{
    /**
     * Read a topic's reviewer material.
     */
    public function show(Request $request, Lesson $lesson): View
    {
        $user = $request->user();
        // Fetch the lesson's topic and subject for the access check and the view.
        $lesson->load('topic.subject');
        // 404 unless this student's track covers the lesson's subject.
        abort_unless($user->coversSubject($lesson->topic->subject), 404);

        // The student's progress row, or a new, unsaved one.
        $progress = LessonProgress::firstOrNew([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        // Stamp the moment the lesson was last opened.
        $progress->last_viewed_at = now();

        // Opening a lesson counts as at least 10% progress.
        if ($progress->progress < 10) {
            $progress->progress = 10;
        }

        // Save the row (insert on first read, update after that).
        $progress->save();

        return view('review.lesson', [
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
    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();
        // Fetch the lesson's topic and subject for the access check.
        $lesson->load('topic.subject');
        // 404 unless this student's track covers the lesson's subject.
        abort_unless($user->coversSubject($lesson->topic->subject), 404);

        // Create or update the progress row as fully complete, stamping both
        // timestamps.
        LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['progress' => 100, 'last_viewed_at' => now(), 'completed_at' => now()],
        );

        return redirect()
            ->route('review.lesson', $lesson)
            ->with('status', 'Marked "'.$lesson->title.'" as reviewed.');
    }
}
