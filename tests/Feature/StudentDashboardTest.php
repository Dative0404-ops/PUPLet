<?php

// Feature tests live here; this class covers the student dashboard.
namespace Tests\Feature;

// Eloquent model for a quiz definition.
use App\Models\Quiz;
// Eloquent model for one recorded quiz sitting.
use App\Models\QuizAttempt;
// Eloquent model for a study goal shown on the dashboard.
use App\Models\StudyGoal;
// Eloquent model for a subject (LET area bucket).
use App\Models\Subject;
// Eloquent model for a topic inside a subject.
use App\Models\Topic;
// Eloquent model for an account.
use App\Models\User;
// The seeder that builds the sample LET catalog used by several tests.
use Database\Seeders\LetCatalogSeeder;
// RefreshDatabase gives every test a fresh database.
use Illuminate\Foundation\Testing\RefreshDatabase;
// Our shared base TestCase that boots the Laravel application.
use Tests\TestCase;

// Covers the welcome dashboard: progress, per-area scores, unfinished
// tests, weakest topics and daily-target progress.
class StudentDashboardTest extends TestCase
{
    // Refreshes the database before each test so runs do not interfere.
    use RefreshDatabase;

    // Protects that the dashboard is private.
    public function test_dashboard_requires_authentication(): void
    {
        // A guest is redirected to /login instead of seeing the dashboard.
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // Checks the dashboard greetings show the student's name and track.
    public function test_dashboard_shows_the_student_name_and_track(): void
    {
        // Creates a student with a fixed name for predictable page text.
        $student = $this->student(['name' => 'Maria Santos']);

        // Signs in and loads the dashboard, checking each section heading.
        $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertSee('Secondary (BSED) - English')
            ->assertSee('Overall progress')
            ->assertSee('Subject performance')
            ->assertSee('Continue reviewing')
            ->assertSee('Recommended topics')
            ->assertSee('Upcoming study goals');
    }

    // Checks the dashboard reports overall progress from completed attempts.
    public function test_dashboard_reports_overall_progress(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose dashboard we are checking.
        $student = $this->student();

        // Two attempts: 14/20 GenEd and 10/20 ProfEd, 24 of 40 correct overall.
        $this->attempt($student, $this->quiz('gen_ed', 'English', 'Grammar and usage'), 20, 14);
        $this->attempt($student, $this->quiz('prof_ed', 'Assessment in Learning', 'Item analysis and grading'), 20, 10);

        // The dashboard shows the rounded 60% and a completed-quizzes figure.
        $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('60%')
            ->assertSee('Completed quizzes');
    }

    // Checks the dashboard breaks scores down per LET area.
    public function test_dashboard_shows_scores_per_let_area(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose dashboard we are checking.
        $student = $this->student();

        // One GenEd attempt and one Specialization (English) attempt.
        $this->attempt($student, $this->quiz('gen_ed', 'English', 'Grammar and usage'), 20, 18);
        $this->attempt($student, $this->quiz('specialization', 'English', 'English literature'), 20, 11);

        // The dashboard names each area and the drilled specialization topic.
        $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('General Education')
            ->assertSee('Professional Education')
            ->assertSee('Specialization')
            ->assertSee('English literature');
    }

    // Checks the dashboard surfaces an unfinished test with its progress.
    public function test_dashboard_shows_the_unfinished_test(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose dashboard we are checking.
        $student = $this->student();

        // Creates an open attempt (no completed_at) partway through.
        QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $this->quiz('prof_ed', 'Assessment in Learning', 'Item analysis and grading')->id,
            'total_items' => 20,
            'answered_items' => 8,
            'score' => 6,
            'started_at' => now()->subMinutes(30),
        ]);

        // The dashboard shows the unfinished-test block and its answered count.
        $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Unfinished test')
            ->assertSee('Practice set: Item analysis and grading')
            ->assertSee('8 of 20 items answered');
    }

    // Checks the weakest topics are listed before the stronger ones.
    public function test_dashboard_ranks_the_weakest_topics_first(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose dashboard we are checking.
        $student = $this->student();

        // A weak Statistics attempt (9/20) and a strong Reading attempt (18/20).
        $this->attempt($student, $this->quiz('gen_ed', 'Mathematics', 'Statistics and probability'), 20, 9);
        $this->attempt($student, $this->quiz('gen_ed', 'English', 'Reading comprehension'), 20, 18);

        // assertSeeInOrder checks the weak topic appears before the strong one.
        $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Statistics and probability', 'Reading comprehension'])
            // 9 of 20 correct is shown as 45%.
            ->assertSee('45%');
    }

    // Checks the dashboard shows progress against the daily item target.
    public function test_dashboard_shows_the_daily_target_progress(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose dashboard we are checking.
        $student = $this->student();

        // One completed 20-item attempt today.
        $this->attempt($student, $this->quiz('gen_ed', 'English', 'Grammar and usage'), 20, 15);

        // A daily target of 50 items dated today.
        StudyGoal::create([
            'user_id' => $student->id,
            'title' => 'Answer 50 review items',
            'type' => StudyGoal::TYPE_DAILY_TARGET,
            'target_date' => now()->toDateString(),
            'target_items' => 50,
        ]);

        // A review session planned for tomorrow evening.
        StudyGoal::create([
            'user_id' => $student->id,
            'title' => 'Timed drill: Biology',
            'type' => StudyGoal::TYPE_REVIEW_SESSION,
            'target_date' => now()->addDay()->toDateString(),
            'target_time' => '19:00',
        ]);

        // The dashboard shows today's items against the target and tomorrow's goal.
        $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('20 / 50')
            ->assertSee('Timed drill: Biology')
            ->assertSee('Tomorrow');
    }

    /**
     * Seed the subject, topic, lesson, and quiz catalog.
     */
    // Helper: run the LET catalog seeder so real subjects/topics/quizzes exist.
    private function seedCatalog(): void
    {
        // seed() runs a database seeder within the test's transaction.
        $this->seed(LetCatalogSeeder::class);
    }

    /**
     * Create a student with a LET track.
     *
     * @param  array<string, mixed>  $attributes
     */
    // Helper: build a student, defaulting to a Secondary - English track.
    private function student(array $attributes = []): User
    {
        // array_merge lets the caller override the defaults below.
        return User::factory()->create(array_merge([
            'exam_type' => 'secondary',
            'specialization' => 'English',
        ], $attributes));
    }

    /**
     * Find a practice quiz by area, subject, and name.
     */
    // Helper: look up a practice quiz via subject area, subject and topic name.
    private function quiz(string $area, string $subject, string $topic): Quiz
    {
        // First find the owning subject by area and name.
        $subjectModel = Subject::where('area', $area)->where('name', $subject)->firstOrFail();

        // Then find the topic by name under that subject.
        $topicModel = Topic::where('subject_id', $subjectModel->id)->where('name', $topic)->firstOrFail();

        // Finally take that topic's first related quiz.
        return $topicModel->quizzes()->firstOrFail();
    }

    /**
     * Record a completed attempt for a student.
     */
    // Helper: write a finished attempt row (used for progress calculations).
    // NOTE: pre-existing flake — completed_at = now()->subHour() uses the app's
    // UTC timezone while the developer's clock is UTC+8, so between 08:00 and
    // 09:00 Singapore time this lands on the previous UTC day and the "today's
    // items" assertion in the daily-target test can fail. Do not change the value.
    // NOTE: pre-existing flake — see above.
    private function attempt(User $student, Quiz $quiz, int $items, int $correct): QuizAttempt
    {
        // Inserts an attempt that counts as complete because completed_at is set.
        return QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'total_items' => $items,
            'answered_items' => $items,
            'score' => $correct,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
    }
}
