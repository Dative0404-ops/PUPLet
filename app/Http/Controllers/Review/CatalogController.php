<?php

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
// The incoming HTTP request.
use Illuminate\Http\Request;
// In-memory collection returned by Eloquent.
use Illuminate\Support\Collection;
// Facade used to run raw database queries.
use Illuminate\Support\Facades\DB;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * Subject selection: the entry point of the reviewer.
 */
class CatalogController extends Controller
{
    /**
     * A short description of each LET area, shown on the selection page.
     *
     * @var array<string, string>
     */
    private const AREA_BLURBS = [
        'gen_ed' => 'The general education subjects every teacher must master.',
        'prof_ed' => 'The professional and pedagogical subjects at the heart of the LET.',
        'specialization' => 'The content area of your chosen major.',
    ];

    /**
     * Choose a subject to review.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Every subject the student's LET track covers, with a topic count,
        // grouped by LET area.
        $subjects = Subject::query()
            // Add a topics_count attribute via a subquery count.
            ->withCount('topics')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Subject $subject) => $user->coversSubject($subject))
            ->groupBy('area');

        // Published question counts per subject id.
        $questions = $this->questionsPerSubject();
        // Reviewed-lesson counts per subject id for this student.
        $reviewed = $this->reviewedLessonsPerSubject($user);

        // The per-area sections shown on the page.
        $areas = [];

        // Walk each known LET area in the order the app defines.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // The subjects in this area, or an empty collection when none.
            $areaSubjects = $subjects->get($area, new Collection);

            // Skip areas that have no subjects for this track.
            if ($areaSubjects->isEmpty()) {
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
                // Per-subject statistics shown as cards.
                'subjects' => $areaSubjects->map(fn (Subject $subject) => [
                    // The subject model.
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
    public function subject(Request $request, Subject $subject): View
    {
        $user = $request->user();
        // 404 unless this student's track covers the subject.
        abort_unless($user->coversSubject($subject), 404);

        // The subject's topics, with a count of published questions and their
        // lessons and quizzes fetched in the same query.
        $topics = $subject->topics()
            ->withCount(['questions' => fn ($query) => $query->published()])
            ->with(['lessons', 'quizzes'])
            ->orderBy('sort_order')
            ->get();

        // This student's lesson-progress rows for those lessons, keyed by
        // lesson id for quick lookup.
        $progress = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $topics->pluck('lessons')->flatten()->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        // One display row per topic.
        $rows = $topics->map(function (Topic $topic) use ($progress) {
            $lesson = $topic->lessons->first();
            // The student's progress row for that lesson, if any.
            $record = $lesson ? $progress->get($lesson->id) : null;

            return [
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

        return view('review.subject', [
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
    public function topic(Request $request, Topic $topic): View
    {
        $user = $request->user();
        // Fetch the relations the hub page needs.
        $topic->load('subject', 'lessons', 'quizzes');
        // 404 unless this student's track covers the topic's subject.
        abort_unless($user->coversSubject($topic->subject), 404);

        // The topic's first lesson, if it has one.
        $lesson = $topic->lessons->first();

        return view('review.topic', [
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
                    ->where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->first()
                : null,
            // How many of the topic's questions the student has bookmarked.
            'bookmarks' => QuestionBookmark::query()
                ->where('user_id', $user->id)
                // Only bookmarks whose question belongs to this topic.
                ->whereHas('question', fn ($query) => $query->where('topic_id', $topic->id))
                ->count(),
        ]);
    }

    /**
     * How many published questions exist per subject.
     *
     * @return Collection<int, int>
     */
    private function questionsPerSubject(): Collection
    {
        // Count published questions per subject with a raw query.
        return DB::table('questions')
            ->where('status', Question::STATUS_PUBLISHED)
            // Join topics so we can reach the subject id.
            ->join('topics', 'topics.id', '=', 'questions.topic_id')
            ->groupBy('topics.subject_id')
            // Return a map of subject id to count.
            ->pluck(DB::raw('COUNT(*)'), 'topics.subject_id');
    }

    /**
     * How many lessons of each subject the student has marked as reviewed.
     *
     * @return Collection<int, int>
     */
    private function reviewedLessonsPerSubject(User $user): Collection
    {
        // Count completed lessons per subject for one student with a raw query.
        return DB::table('lesson_progresses')
            // Join lessons to reach each lesson's topic.
            ->join('lessons', 'lessons.id', '=', 'lesson_progresses.lesson_id')
            // Join topics to reach the subject.
            ->join('topics', 'topics.id', '=', 'lessons.topic_id')
            // Only this student's progress rows.
            ->where('lesson_progresses.user_id', $user->id)
            // Only lessons that were actually completed.
            ->whereNotNull('lesson_progresses.completed_at')
            ->groupBy('topics.subject_id')
            // Return a map of subject id to completed-lesson count.
            ->pluck(DB::raw('COUNT(*)'), 'topics.subject_id');
    }
}
