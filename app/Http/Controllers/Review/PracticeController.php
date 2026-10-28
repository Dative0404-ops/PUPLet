<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a quiz (the per-topic practice set).
use App\Models\Quiz;
// Eloquent model for a subject.
use App\Models\Subject;
// Eloquent model for a topic inside a subject.
use App\Models\Topic;
// Helper that builds attempts and question pools.
use App\Support\AttemptBuilder;
// Return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// The incoming HTTP request and its input.
use Illuminate\Http\Request;
// Validation helper providing the "value in a fixed list" rule.
use Illuminate\Validation\Rule;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * The quick start for a practice drill: pick a topic and how many items to
 * answer, then begin straight away without browsing the catalog.
 */
class PracticeController extends Controller
{
    /**
     * The number of items a practice drill offers.
     *
     * @var list<int>
     */
    private const ITEM_CHOICES = [10, 15, 20, 25, 30];

    /**
     * The item count a practice quiz is created with when a topic has none.
     */
    private const DEFAULT_ITEM_COUNT = 20;

    /**
     * The minutes a practice quiz is created with when a topic has none.
     */
    private const DEFAULT_TIME_LIMIT = 25;

    /**
     * Choose the topic and the length of the drill.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Subjects the student's track covers, each with its topics and their
        // published-question counts.
        $subjects = Subject::query()
            ->with(['topics' => fn ($query) => $query
                ->withCount(['questions' => fn ($questions) => $questions->published()])
                ->orderBy('sort_order')
                ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Subject $subject) => $user->coversSubject($subject))
            ->values();

        // Subject groups that only contain topics with questions.
        $topics = $subjects->map(fn (Subject $subject) => [
            // The subject used for the group heading.
            'subject' => $subject,
            // Its topics that actually have published questions.
            'topics' => $subject->topics
                ->filter(fn (Topic $topic) => $topic->questions_count > 0)
                // Re-index the survivors.
                ->values(),
        ])->filter(fn (array $row) => $row['topics']->isNotEmpty())->values();

        return view('review.practice', [
            // The subject groups with their selectable topics.
            'topicGroups' => $topics,
            // The allowed drill lengths.
            'itemChoices' => self::ITEM_CHOICES,
            // The default length preselected in the form.
            'defaultItems' => self::DEFAULT_ITEM_COUNT,
            // A label describing the student's LET track.
            'trackLabel' => $user->trackLabel(),
            // The student's five most recent completed practice attempts.
            'recentAttempts' => $user->quizAttempts()
                ->completed()
                // Also fetch the quiz, topic, and subject in the same query.
                ->with('quiz.topic.subject')
                // Newest completed first.
                ->latest('completed_at')
                // At most five rows.
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Start the drill on the chosen topic.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Check the chosen topic and drill length.
        $data = $request->validate([
            // The topic must exist in the topics table.
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            // The item count must be one of the offered choices.
            'items' => ['required', 'integer', Rule::in(self::ITEM_CHOICES)],
        ]);

        // The chosen topic with its subject, or a 404 when missing.
        $topic = Topic::query()->with('subject')->findOrFail($data['topic_id']);
        // 404 unless this student's track covers the topic's subject.
        abort_unless($user->coversSubject($topic->subject), 404);

        // The topic's practice quiz, and a random sample of its questions.
        $quiz = $this->practiceQuiz($topic);
        $questions = AttemptBuilder::poolForQuiz($quiz)->shuffle()->take((int) $data['items']);

        // Save a new attempt built from those questions.
        $attempt = AttemptBuilder::fromQuestions($user, $quiz, $questions);

        // A null attempt means no published questions were available.
        if ($attempt === null) {
            // Return to the form, keeping the input, and show an error.
            return back()
                ->withInput()
                ->withErrors(['topic_id' => 'This topic has no published questions yet. Please choose another one.']);
        }

        // Open the new attempt with a confirmation message.
        return redirect()
            ->route('review.attempt', $attempt)
            ->with('status', 'Your '.$questions->count().'-item practice drill is ready. Good luck.');
    }

    /**
     * The practice quiz of a topic, created the first time it is needed.
     */
    private function practiceQuiz(Topic $topic): Quiz
    {
        // Find the topic's practice quiz, creating it with default size and
        // time limit when it does not exist yet.
        return Quiz::firstOrCreate(
            ['topic_id' => $topic->id, 'title' => 'Practice set: '.$topic->name],
            ['item_count' => self::DEFAULT_ITEM_COUNT, 'time_limit_minutes' => self::DEFAULT_TIME_LIMIT],
        );
    }
}
