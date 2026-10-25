<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a quiz (the per-topic practice set).
use App\Models\Quiz;
// Eloquent model for a subject.
use App\Models\Subject;
// Eloquent model for a topic inside a subject.
use App\Models\Topic;
// Support helper that builds attempts and question pools.
use App\Support\AttemptBuilder;
// Return type for actions that send the browser elsewhere.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request and its input.
use Illuminate\Http\Request;
// Validation helper providing the "value in a fixed list" rule.
use Illuminate\Validation\Rule;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * The quick start for a practice drill: pick a topic and how many items to
 * answer, then begin straight away without browsing the catalog.
 */
// Controller for the quick "practice drill" starter screen.
class PracticeController extends Controller
{
    /**
     * The number of items a practice drill offers.
     *
     * @var list<int>
     */
    // The item-count options the student may choose from.
    private const ITEM_CHOICES = [10, 15, 20, 25, 30];

    /**
     * The item count a practice quiz is created with when a topic has none.
     */
    // Default item count stored on a newly created practice quiz.
    private const DEFAULT_ITEM_COUNT = 20;

    /**
     * The minutes a practice quiz is created with when a topic has none.
     */
    // Default time limit stored on a newly created practice quiz.
    private const DEFAULT_TIME_LIMIT = 25;

    /**
     * Choose the topic and the length of the drill.
     */
    // Shows the practice setup screen (topic picker plus length choice).
    public function index(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();

        // Load subjects with their topics and per-topic question counts.
        $subjects = Subject::query()
            // Eager-load topics, each carrying a published-question count.
            ->with(['topics' => fn ($query) => $query
                // Count only published questions per topic.
                ->withCount(['questions' => fn ($questions) => $questions->published()])
                // Order topics by the manual sort column.
                ->orderBy('sort_order')
                // Then alphabetically.
                ->orderBy('name'),
            ])
            // Order subjects by the manual sort column.
            ->orderBy('sort_order')
            // Then alphabetically.
            ->orderBy('name')
            // Run the query.
            ->get()
            // Keep only subjects the student's track covers.
            ->filter(fn (Subject $subject) => $user->coversSubject($subject))
            // Re-index the collection from zero.
            ->values();

        // Build subject groups that only contain topics with questions.
        $topics = $subjects->map(fn (Subject $subject) => [
            // The subject used for the group heading.
            'subject' => $subject,
            // Its topics that actually have published questions.
            'topics' => $subject->topics
                // Drop topics with no questions.
                ->filter(fn (Topic $topic) => $topic->questions_count > 0)
                // Re-index the survivors.
                ->values(),
        ])->filter(fn (array $row) => $row['topics']->isNotEmpty())->values();

        // Render the practice setup page.
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
                // Only finished attempts.
                ->completed()
                // Eager-load the quiz, topic, and subject.
                ->with('quiz.topic.subject')
                // Newest completed first.
                ->latest('completed_at')
                // At most five rows.
                ->limit(5)
                // Run the query.
                ->get(),
        ]);
    }

    /**
     * Start the drill on the chosen topic.
     */
    // Handles the form that starts a practice drill.
    public function store(Request $request): RedirectResponse
    {
        // The signed-in student.
        $user = $request->user();

        // Validate the chosen topic and drill length.
        $data = $request->validate([
            // The topic must exist in the topics table.
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            // The item count must be one of the offered choices.
            'items' => ['required', 'integer', Rule::in(self::ITEM_CHOICES)],
        ]);

        // Load the chosen topic with its subject, or fail with a 404.
        $topic = Topic::query()->with('subject')->findOrFail($data['topic_id']);
        // 404 unless this student's track covers the topic's subject.
        abort_unless($user->coversSubject($topic->subject), 404);

        // Find, or create, the topic's practice quiz.
        $quiz = $this->practiceQuiz($topic);
        // Take a random sample of the quiz's questions for this drill.
        $questions = AttemptBuilder::poolForQuiz($quiz)->shuffle()->take((int) $data['items']);

        // Persist a new attempt built from those questions.
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
    // Private helper: finds or creates the practice quiz for a topic.
    private function practiceQuiz(Topic $topic): Quiz
    {
        // Look up the topic's practice quiz by topic and title, creating it
        // with default size and time limit when it does not exist yet.
        return Quiz::firstOrCreate(
            // The unique attributes identifying this practice quiz.
            ['topic_id' => $topic->id, 'title' => 'Practice set: '.$topic->name],
            // Defaults applied only when creating a new row.
            ['item_count' => self::DEFAULT_ITEM_COUNT, 'time_limit_minutes' => self::DEFAULT_TIME_LIMIT],
        );
    }
}
