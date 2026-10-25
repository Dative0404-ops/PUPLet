<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model tracking how far a student got through a lesson.
use App\Models\LessonProgress;
// Eloquent model for a single question in the bank.
use App\Models\Question;
// Eloquent model for a bookmarked question.
use App\Models\QuestionBookmark;
// Subject model; used here for its AREA_LABELS map.
use App\Models\Subject;
// Eloquent model for a topic inside a subject.
use App\Models\Topic;
// Eloquent model for a student (or admin) account.
use App\Models\User;
// Wraps the incoming HTTP request.
use Illuminate\Http\Request;
// Generic in-memory collection returned by Eloquent.
use Illuminate\Support\Collection;
// Facade used to run raw database queries.
use Illuminate\Support\Facades\DB;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Subject selection: the entry point of the reviewer.
 */
// Controller for browsing subjects, topics, and the topic hub.
class CatalogController extends Controller
{
    /**
     * A short description of each LET area, shown on the selection page.
     *
     * @var array<string, string>
     */
    // Fixed blurb text keyed by each LET area code.
    private const AREA_BLURBS = [
        // Blurb for the General Education area.
        'gen_ed' => 'The general education subjects every teacher must master.',
        // Blurb for the Professional Education area.
        'prof_ed' => 'The professional and pedagogical subjects at the heart of the LET.',
        // Blurb for the chosen-major specialisation area.
        'specialization' => 'The content area of your chosen major.',
    ];

    /**
     * Choose a subject to review.
     */
    // Shows the catalog of subjects available to the student's track.
    public function index(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();

        // Load every subject with a topic count, sorted for display.
        $subjects = Subject::query()
            // Add a topics_count attribute via a subquery count.
            ->withCount('topics')
            // Order by the manual sort column.
            ->orderBy('sort_order')
            // Run the query.
            ->get()
            // Keep only subjects the student's LET track covers.
            ->filter(fn (Subject $subject) => $user->coversSubject($subject))
            // Group the remaining subjects by their LET area.
            ->groupBy('area');

        // Published question counts per subject id.
        $questions = $this->questionsPerSubject();
        // Reviewed-lesson counts per subject id for this student.
        $reviewed = $this->reviewedLessonsPerSubject($user);

        // Build the per-area sections shown on the page.
        $areas = [];

        // Walk each known LET area in its canonical order.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // The subjects in this area, or an empty collection when none.
            $areaSubjects = $subjects->get($area, new Collection);

            // Skip areas that have no subjects for this track.
            if ($areaSubjects->isEmpty()) {
                // Move on to the next area.
                continue;
            }

            // One section of the page: a heading plus its subject cards.
            $areas[] = [
                // The area code, used as a key.
                'area' => $area,
                // Friendly area name for the heading.
                'label' => $label,
                // Description shown under the heading.
                'blurb' => self::AREA_BLURBS[$area] ?? '',
                // Per-subject statistics rendered as cards.
                'subjects' => $areaSubjects->map(fn (Subject $subject) => [
                    // The subject model itself.
                    'subject' => $subject,
                    // Number of topics in the subject.
                    'topics' => $subject->topics_count,
                    // Number of published questions, defaulting to zero.
                    'questions' => (int) ($questions[$subject->id] ?? 0),
                    // Number of lessons this student has marked reviewed.
                    'reviewed' => (int) ($reviewed[$subject->id] ?? 0),
                ])->values(),
            ];
        }

        // Render the catalog page.
        return view('review.catalog', [
            // The area sections built above.
            'areas' => $areas,
            // A label describing the student's LET track.
            'trackLabel' => $user->trackLabel(),
        ]);
    }

    /**
     * The topics inside one subject.
     */
    // Lists a subject's topics with progress and question counts.
    public function subject(Request $request, Subject $subject): View
    {
        // The signed-in student.
        $user = $request->user();
        // 404 unless this student's track covers the requested subject.
        abort_unless($user->coversSubject($subject), 404);

        // Load the subject's topics with counts and relations.
        $topics = $subject->topics()
            // Count only published questions per topic via a subquery.
            ->withCount(['questions' => fn ($query) => $query->published()])
            // Eager-load each topic's lessons and quizzes.
            ->with(['lessons', 'quizzes'])
            // Order by the manual sort column.
            ->orderBy('sort_order')
            // Run the query.
            ->get();

        // This student's lesson-progress rows for the listed lessons.
        $progress = LessonProgress::query()
            // Scope to the current student.
            ->where('user_id', $user->id)
            // Limit to lessons belonging to the topics above.
            ->whereIn('lesson_id', $topics->pluck('lessons')->flatten()->pluck('id'))
            // Run the query.
            ->get()
            // Key the rows by lesson id for quick lookup.
            ->keyBy('lesson_id');

        // Build one display row per topic.
        $rows = $topics->map(function (Topic $topic) use ($progress) {
            // The topic's first lesson, if it has one.
            $lesson = $topic->lessons->first();
            // The student's progress row for that lesson, if any.
            $record = $lesson ? $progress->get($lesson->id) : null;

            // The data each topic row needs.
            return [
                // The topic model.
                'topic' => $topic,
                // Its lesson, used for the "read" link.
                'lesson' => $lesson,
                // Its first quiz, used for the "practice" link.
                'quiz' => $topic->quizzes->first(),
                // Number of published questions in the topic.
                'questions' => $topic->questions_count,
                // Whether the student finished the lesson.
                'completed' => $record?->completed_at !== null,
                // Whether the student has started the lesson at all.
                'started' => $record !== null,
            ];
        });

        // Render the subject page.
        return view('review.subject', [
            // The subject being viewed.
            'subject' => $subject,
            // The topic rows built above.
            'topics' => $rows,
            // How many topics are already finished.
            'reviewed' => $rows->where('completed', true)->count(),
        ]);
    }

    /**
     * The hub for a single topic: reviewer material and the practice quiz.
     */
    // Shows the hub page for one topic.
    public function topic(Request $request, Topic $topic): View
    {
        // The signed-in student.
        $user = $request->user();
        // Load the relations the hub page needs.
        $topic->load('subject', 'lessons', 'quizzes');
        // 404 unless this student's track covers the topic's subject.
        abort_unless($user->coversSubject($topic->subject), 404);

        // The topic's first lesson, if it has one.
        $lesson = $topic->lessons->first();

        // Render the topic hub.
        return view('review.topic', [
            // The topic being viewed.
            'topic' => $topic,
            // Its lesson, used for the "read" call to action.
            'lesson' => $lesson,
            // Its first quiz, used for the "practice" call to action.
            'quiz' => $topic->quizzes->first(),
            // Count of published questions in the topic.
            'questions' => $topic->questions()->published()->count(),
            // The student's progress row for the lesson, if any.
            'progress' => $lesson
                ? LessonProgress::query()
                    // Scope to the current student.
                    ->where('user_id', $user->id)
                    // Scope to this lesson.
                    ->where('lesson_id', $lesson->id)
                    // Return the first matching row.
                    ->first()
                : null,
            // How many of the topic's questions the student has bookmarked.
            'bookmarks' => QuestionBookmark::query()
                // Scope to the current student.
                ->where('user_id', $user->id)
                // Limit to bookmarks whose question belongs to this topic.
                ->whereHas('question', fn ($query) => $query->where('topic_id', $topic->id))
                // Count the matches.
                ->count(),
        ]);
    }

    /**
     * How many published questions exist per subject.
     *
     * @return Collection<int, int>
     */
    // Private helper: raw query returning published-question counts by subject.
    private function questionsPerSubject(): Collection
    {
        // Query the questions table directly to aggregate by subject.
        return DB::table('questions')
            // Only count published questions.
            ->where('status', Question::STATUS_PUBLISHED)
            // Join topics so we can reach the subject id.
            ->join('topics', 'topics.id', '=', 'questions.topic_id')
            // Group rows per subject.
            ->groupBy('topics.subject_id')
            // Return a map of subject id to count.
            ->pluck(DB::raw('COUNT(*)'), 'topics.subject_id');
    }

    /**
     * How many lessons of each subject the student has marked as reviewed.
     *
     * @return Collection<int, int>
     */
    // Private helper: counts completed lessons per subject for one student.
    private function reviewedLessonsPerSubject(User $user): Collection
    {
        // Query the progress table directly for aggregation.
        return DB::table('lesson_progresses')
            // Join lessons to reach each lesson's topic.
            ->join('lessons', 'lessons.id', '=', 'lesson_progresses.lesson_id')
            // Join topics to reach the subject.
            ->join('topics', 'topics.id', '=', 'lessons.topic_id')
            // Only this student's progress rows.
            ->where('lesson_progresses.user_id', $user->id)
            // Only lessons actually completed (they have a completion time).
            ->whereNotNull('lesson_progresses.completed_at')
            // Group per subject.
            ->groupBy('topics.subject_id')
            // Return a map of subject id to completed-lesson count.
            ->pluck(DB::raw('COUNT(*)'), 'topics.subject_id');
    }
}
