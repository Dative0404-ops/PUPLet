<?php

// Feature tests live here; this class covers the student account area.
namespace Tests\Feature;

// Eloquent model for a question bank item.
use App\Models\Question;
// Eloquent model for a quiz definition.
use App\Models\Quiz;
// Eloquent model for one recorded quiz sitting.
use App\Models\QuizAttempt;
// Eloquent model for a study goal created in the planner.
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
// The Hash facade checks a stored password hash against a plain-text value.
use Illuminate\Support\Facades\Hash;
// Our shared base TestCase that boots the Laravel application.
use Tests\TestCase;

// Covers profile, history, analytics, planner and settings under /account.
class StudentAccountTest extends TestCase
{
    // Refreshes the database before each test so runs do not interfere.
    use RefreshDatabase;

    // Protects that every account page requires a signed-in user.
    public function test_account_pages_require_authentication(): void
    {
        // A guest is redirected to /login from each account page.
        $this->get('/account/profile')->assertRedirect('/login');
        // History page is likewise protected.
        $this->get('/account/history')->assertRedirect('/login');
        // Analytics page is likewise protected.
        $this->get('/account/analytics')->assertRedirect('/login');
        // Planner page is likewise protected.
        $this->get('/account/planner')->assertRedirect('/login');
        // Settings page is likewise protected.
        $this->get('/account/settings')->assertRedirect('/login');
    }

    // Checks the profile page shows the student's identity and preferences.
    public function test_the_profile_shows_the_students_details(): void
    {
        // Creates a student with a fixed name so the page text is predictable.
        $student = $this->student(['name' => 'Maria Santos']);

        // Signs in as that student, opens the profile, and checks its key facts.
        $this->actingAs($student)
            ->get('/account/profile')
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertSee($student->email)
            // The track is rendered as a friendly label, not raw codes.
            ->assertSee('Secondary (BSED) - English')
            // The study-preference labels are present on the page.
            ->assertSee('Daily item target')
            ->assertSee('Practice timer');
    }

    // Checks the profile form updates name, email and LET track.
    public function test_the_profile_updates_the_name_email_and_let_track(): void
    {
        // Starts from a default student (Secondary - English).
        $student = $this->student();

        // Signs in and submits the profile form with new values.
        $this->actingAs($student)
            ->put('/account/profile', [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.test',
                'exam_type' => 'elementary',
                'specialization' => 'MAPEH',
            ])
            // A successful update redirects back to the profile page.
            ->assertRedirect(route('account.profile'));

        // Confirms the same row was actually updated in the database.
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.test',
            'exam_type' => 'elementary',
            'specialization' => 'MAPEH',
        ]);
    }

    // Protects that an email already used by another account is rejected.
    public function test_the_profile_rejects_an_email_already_in_use(): void
    {
        // The student who is editing their profile.
        $student = $this->student();
        // A second student who already owns the email we will try to claim.
        $this->student(['email' => 'taken@example.test']);

        // Signs in, remembers the form page, and tries to save the taken email.
        $this->actingAs($student)
            ->from('/account/profile')
            ->put('/account/profile', [
                'name' => $student->name,
                'email' => 'taken@example.test',
                'exam_type' => 'secondary',
                'specialization' => 'English',
            ])
            // Validation should flag the email field.
            ->assertSessionHasErrors('email');

        // Confirms the original email was left untouched.
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'email' => $student->email,
        ]);
    }

    // Protects that a specialization must belong to the chosen exam type.
    public function test_the_profile_rejects_a_specialization_from_another_exam_type(): void
    {
        // Starts from a Secondary - English student.
        $student = $this->student();

        // Tries to save an EPP/TLE major, which is not a Secondary major here.
        $this->actingAs($student)
            ->from('/account/profile')
            ->put('/account/profile', [
                'name' => $student->name,
                'email' => $student->email,
                'exam_type' => 'secondary',
                'specialization' => 'EPP / TLE',
            ])
            // The specialization field should raise a validation error.
            ->assertSessionHasErrors('specialization');

        // Confirms the stored track is unchanged.
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'exam_type' => 'secondary',
            'specialization' => 'English',
        ]);
    }

    // Checks the history page lists attempts and filters by quiz type.
    public function test_the_history_lists_attempts_and_filters_by_type(): void
    {
        // Builds the sample catalog so real topics/quizzes exist.
        $this->seedCatalog();

        // The student whose history we are checking.
        $student = $this->student();
        // Finds the Grammar practice quiz and gives it a readable title.
        $practice = $this->quiz('gen_ed', 'English', 'Grammar and usage');
        $practice->update(['title' => 'Grammar drill']);

        // Creates a mock-exam quiz for the second attempt type.
        $mock = $this->mockQuiz();

        // Records one practice attempt (15/20) and one mock attempt (70/100).
        $this->attempt($student, $practice, 20, 15);
        $this->attempt($student, $mock, 100, 70);

        // The unfiltered history shows both attempts and their scores.
        $this->actingAs($student)
            ->get('/account/history')
            ->assertOk()
            ->assertSee('Grammar drill')
            ->assertSee('LET Mock Examination')
            ->assertSee('15 / 20')
            ->assertSee('75%')
            ->assertSee('70%');

        // Filtering by type=practice shows only the practice attempt.
        $this->actingAs($student)
            ->get('/account/history?type=practice')
            ->assertOk()
            ->assertSee('Grammar drill')
            // assertDontSee asserts the text is absent from the page.
            ->assertDontSee('LET Mock Examination');

        // Filtering by type=mock shows only the mock attempt.
        $this->actingAs($student)
            ->get('/account/history?type=mock')
            ->assertOk()
            ->assertSee('LET Mock Examination')
            ->assertDontSee('Grammar drill');
    }

    // Checks the history page paginates once there are more than fifteen attempts.
    public function test_the_history_paginates_past_fifteen_attempts(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose history we are checking.
        $student = $this->student();
        // Finds the practice quiz to record attempts against.
        $quiz = $this->quiz('gen_ed', 'English', 'Grammar and usage');

        // Records 16 attempts; the extra one forces a second page.
        for ($index = 1; $index <= 16; $index++) {
            // Each attempt is completed a minute earlier to keep a stable order.
            $this->attempt($student, $quiz, 10, 5, now()->subMinutes($index));
        }

        // The first page reports page 1 of 2.
        $this->actingAs($student)
            ->get('/account/history')
            ->assertOk()
            ->assertSee('Page 1 of 2');

        // The second page reports page 2 of 2.
        $this->actingAs($student)
            ->get('/account/history?page=2')
            ->assertOk()
            ->assertSee('Page 2 of 2');
    }

    // Checks an unfinished attempt is reported as still in progress.
    public function test_the_history_reports_an_open_attempt(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose history we are checking.
        $student = $this->student();
        // Finds the practice quiz to attach the open attempt to.
        $quiz = $this->quiz('gen_ed', 'English', 'Grammar and usage');

        // Creates an attempt with no completed_at, so it counts as open.
        QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'total_items' => 10,
            'answered_items' => 4,
            'started_at' => now()->subMinutes(5),
        ]);

        // The history shows the in-progress state and its answered count.
        $this->actingAs($student)
            ->get('/account/history')
            ->assertOk()
            ->assertSee('In progress')
            ->assertSee('4 / 10 answered');
    }

    // Checks the analytics page charts performance by area, subject and topic.
    public function test_the_analytics_chart_performance_by_subject_and_topic(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student whose analytics we are checking.
        $student = $this->student();

        // One strong GenEd attempt (16/20) and one weak ProfEd attempt (8/20).
        $this->attempt($student, $this->quiz('gen_ed', 'English', 'Grammar and usage'), 20, 16);
        $this->attempt($student, $this->quiz('prof_ed', 'Assessment in Learning', 'Item analysis and grading'), 20, 8);

        // The analytics page names each chart and the drilled subject/topic.
        $this->actingAs($student)
            ->get('/account/analytics')
            ->assertOk()
            ->assertSee('Performance by LET area')
            ->assertSee('Performance by subject')
            ->assertSee('Performance by topic')
            ->assertSee('English')
            ->assertSee('Assessment in Learning')
            ->assertSee('Grammar and usage')
            ->assertSee('Item analysis and grading')
            // 16 of 20 correct is shown as 60% overall.
            ->assertSee('60%');
    }

    // Checks the analytics page shows an empty state when there is nothing to chart.
    public function test_the_analytics_show_an_empty_state_without_attempts(): void
    {
        // Builds the sample catalog but records no attempts.
        $this->seedCatalog();

        // The student whose analytics we are checking.
        $student = $this->student();

        // The page shows the empty-state message and omits the topic chart.
        $this->actingAs($student)
            ->get('/account/analytics')
            ->assertOk()
            ->assertSee('nothing to chart yet')
            ->assertDontSee('Performance by topic');
    }

    // Checks the planner can create, update, complete and delete a goal.
    public function test_the_planner_creates_updates_completes_and_deletes_a_goal(): void
    {
        // The student who owns the goal.
        $student = $this->student();

        // Creates a daily-target goal of 40 items dated today.
        $this->actingAs($student)
            ->post('/account/planner', [
                'title' => 'Review GenEd Mathematics',
                'type' => StudyGoal::TYPE_DAILY_TARGET,
                'target_date' => now()->toDateString(),
                'target_items' => 40,
            ])
            // A successful create redirects back to the planner.
            ->assertRedirect(route('account.planner'));

        // Loads the freshly created goal from the database.
        $goal = StudyGoal::where('user_id', $student->id)->firstOrFail();

        // Confirms the 40-item target was stored.
        $this->assertSame(40, $goal->target_items);

        // The planner page lists the goal and its type label.
        $this->actingAs($student)
            ->get('/account/planner')
            ->assertOk()
            ->assertSee('Review GenEd Mathematics')
            ->assertSee('Daily target');

        // Updates the goal into a review session with a new title and time.
        $this->actingAs($student)
            ->put("/account/planner/{$goal->id}", [
                'title' => 'Drill GenEd Mathematics',
                'type' => StudyGoal::TYPE_REVIEW_SESSION,
                'target_date' => now()->addDay()->toDateString(),
                'target_time' => '19:30',
            ])
            ->assertRedirect(route('account.planner'));

        // Reloads the goal to read the saved changes.
        $goal->refresh();
        $this->assertSame('Drill GenEd Mathematics', $goal->title);
        $this->assertSame(StudyGoal::TYPE_REVIEW_SESSION, $goal->type);
        // timeLabel() trims the stored time to HH:MM for display.
        $this->assertSame('19:30', $goal->timeLabel());

        // Marks the goal complete; this redirects back to the planner.
        $this->actingAs($student)
            ->post("/account/planner/{$goal->id}/complete")
            ->assertRedirect(route('account.planner'));

        // Confirms completion recorded a timestamp.
        $this->assertNotNull($goal->refresh()->completed_at);

        // Deletes the goal; this redirects back to the planner.
        $this->actingAs($student)
            ->delete("/account/planner/{$goal->id}")
            ->assertRedirect(route('account.planner'));

        // Confirms the goal row is gone.
        $this->assertDatabaseMissing('study_goals', ['id' => $goal->id]);
    }

    // Protects that each goal type requires its own extra fields.
    public function test_the_planner_requires_the_fields_each_goal_type_needs(): void
    {
        // The student creating the goals.
        $student = $this->student();

        // A review session without a target_time should fail.
        $this->actingAs($student)
            ->from('/account/planner')
            ->post('/account/planner', [
                'title' => 'Evening session',
                'type' => StudyGoal::TYPE_REVIEW_SESSION,
                'target_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('target_time');

        // A daily target without a target_items should fail.
        $this->actingAs($student)
            ->from('/account/planner')
            ->post('/account/planner', [
                'title' => 'Daily items',
                'type' => StudyGoal::TYPE_DAILY_TARGET,
                'target_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('target_items');

        // Confirms neither invalid goal was stored.
        $this->assertDatabaseCount('study_goals', 0);
    }

    // Protects that one student cannot touch another student's goal.
    public function test_a_student_cannot_touch_another_students_goal(): void
    {
        // The student who will try to interfere.
        $student = $this->student();
        // The student who actually owns the private goal.
        $other = $this->student(['email' => 'other@example.test']);

        // Creates a goal belonging to the other student.
        $goal = $other->studyGoals()->create([
            'title' => 'Private goal',
            'type' => StudyGoal::TYPE_DAILY_TARGET,
            'target_date' => now()->toDateString(),
            'target_items' => 25,
        ]);

        // Editing someone else's goal returns 404, as if it did not exist.
        $this->actingAs($student)
            ->put("/account/planner/{$goal->id}", [
                'title' => 'Hijacked',
                'type' => StudyGoal::TYPE_DAILY_TARGET,
                'target_date' => now()->toDateString(),
                'target_items' => 25,
            ])
            ->assertNotFound();

        // Completing someone else's goal also returns 404.
        $this->actingAs($student)
            ->post("/account/planner/{$goal->id}/complete")
            ->assertNotFound();

        // Deleting someone else's goal also returns 404.
        $this->actingAs($student)
            ->delete("/account/planner/{$goal->id}")
            ->assertNotFound();

        // Confirms the private goal survived untouched.
        $this->assertDatabaseHas('study_goals', [
            'id' => $goal->id,
            'title' => 'Private goal',
        ]);
    }

    // Checks the settings page saves the student's study preferences.
    public function test_the_settings_page_saves_study_preferences(): void
    {
        // The student whose preferences we are saving.
        $student = $this->student();

        // Saves an 80-item target, an untimed practice timer and a review landing page.
        $this->actingAs($student)
            ->put('/account/settings/preferences', [
                'daily_target_items' => 80,
                'practice_time_limit' => 'untimed',
                'default_landing_page' => 'review',
            ])
            ->assertRedirect(route('account.settings'));

        // Confirms the values were stored; "untimed" is saved as 0 minutes.
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'daily_target_items' => 80,
            'practice_time_limit_minutes' => 0,
            'default_landing_page' => 'review',
        ]);
    }

    // Protects that invalid preference values are rejected.
    public function test_the_settings_page_validates_study_preferences(): void
    {
        // The student whose preferences we are testing.
        $student = $this->student();

        // Sends an out-of-range target, an unknown timer and an unknown landing page.
        $this->actingAs($student)
            ->from('/account/settings')
            ->put('/account/settings/preferences', [
                'daily_target_items' => 2,
                'practice_time_limit' => 'forever',
                'default_landing_page' => 'nowhere',
            ])
            // All three fields should raise validation errors.
            ->assertSessionHasErrors(['daily_target_items', 'practice_time_limit', 'default_landing_page']);
    }

    // Checks the student can change their password from the settings page.
    public function test_the_password_can_be_changed(): void
    {
        // The student whose password we are changing (factory default: "password").
        $student = $this->student();

        // Submits the current password plus a matching new password pair.
        $this->actingAs($student)
            ->put('/account/settings/password', [
                'current_password' => 'password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertRedirect(route('account.settings'));

        // Confirms the stored hash now matches the new password.
        $this->assertTrue(Hash::check('new-secret-password', $student->refresh()->password));
    }

    // Protects that a wrong current password cannot change the password.
    public function test_changing_the_password_requires_the_current_one(): void
    {
        // The student whose password we try to change.
        $student = $this->student();

        // Submits a wrong current password with a new password pair.
        $this->actingAs($student)
            ->from('/account/settings')
            ->put('/account/settings/password', [
                'current_password' => 'not-my-password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            // The current_password field should raise a validation error.
            ->assertSessionHasErrors('current_password');

        // Confirms the original password is unchanged.
        $this->assertTrue(Hash::check('password', $student->refresh()->password));
    }

    // Checks signing in honours the student's chosen landing page.
    public function test_signing_in_respects_the_landing_page_preference(): void
    {
        // Creates a student who prefers to land on the planner after sign-in.
        $this->student([
            'email' => 'planner@example.test',
            'default_landing_page' => 'planner',
        ]);

        // Signing in should redirect to the planner instead of the dashboard.
        $this->post('/login', [
            'email' => 'planner@example.test',
            'password' => 'password',
        ])->assertRedirect(route('account.planner'));
    }

    // Checks the practice-timer preference controls new attempt time limits.
    public function test_the_practice_timer_preference_controls_new_attempts(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // A student whose preference is 0 minutes, meaning untimed practice.
        $student = $this->student(['practice_time_limit_minutes' => 0]);
        // The topic to practise.
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        // Adds a single question so the quiz has something to serve.
        $this->question($topic, 'Untimed item?');

        // Takes the topic's practice quiz and forces a 25-minute quiz-level limit.
        $quiz = $topic->quizzes()->firstOrFail();
        $quiz->update(['item_count' => 1, 'time_limit_minutes' => 25]);

        // Starts an attempt on that quiz.
        $this->actingAs($student)->post("/review/quizzes/{$quiz->id}/attempts");

        // The newest attempt is the one just started.
        $attempt = QuizAttempt::latest('id')->firstOrFail();
        // The student's 0-minute preference wins, so the attempt is untimed.
        $this->assertSame(0, $attempt->time_limit_minutes);

        // The attempt screen therefore shows no countdown timer.
        $this->actingAs($student)
            ->get("/review/attempts/{$attempt->id}")
            ->assertOk()
            ->assertDontSee('Time remaining');
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
     * Find a topic by area, subject, and name.
     */
    // Helper: look up a topic via its subject's area and name.
    private function topic(string $area, string $subject, string $topic): Topic
    {
        // First find the owning subject by area and name.
        $subjectModel = Subject::where('area', $area)->where('name', $subject)->firstOrFail();

        // Then find the topic by name under that subject.
        return Topic::where('subject_id', $subjectModel->id)->where('name', $topic)->firstOrFail();
    }

    /**
     * Find a practice quiz by area, subject, and topic.
     */
    // Helper: return the practice quiz attached to a topic.
    private function quiz(string $area, string $subject, string $topic): Quiz
    {
        // Resolves the topic, then takes its first related quiz.
        return $this->topic($area, $subject, $topic)->quizzes()->firstOrFail();
    }

    /**
     * Create a full mock examination quiz, which belongs to no single topic.
     */
    // Helper: create a stand-in mock exam quiz for history/analytics tests.
    private function mockQuiz(): Quiz
    {
        // A mock has no topic_id; it is typed and sized directly.
        return Quiz::create([
            'type' => Quiz::TYPE_MOCK,
            'title' => 'LET Mock Examination',
            'item_count' => 100,
            'time_limit_minutes' => 150,
        ]);
    }

    /**
     * Write a single multiple-choice question into a topic.
     */
    // Helper: insert one published-style question into a topic.
    private function question(Topic $topic, string $prompt): Question
    {
        // Creates the question with four options and option 0 marked correct.
        return Question::create([
            'topic_id' => $topic->id,
            'prompt' => $prompt,
            'options' => ['Alpha', 'Beta', 'Gamma', 'Delta'],
            'correct_option' => 0,
            'explanation' => 'Explanation.',
            'sort_order' => 0,
        ]);
    }

    /**
     * Record a completed attempt for a student.
     */
    // Helper: write a finished attempt row; completedAt can be overridden.
    private function attempt(
        User $student,
        Quiz $quiz,
        int $items,
        int $correct,
        $completedAt = null,
    ): QuizAttempt {
        // Inserts an attempt that counts as complete because completed_at is set.
        return QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'total_items' => $items,
            'answered_items' => $items,
            'score' => $correct,
            'started_at' => now()->subHours(2),
            // Defaults to an hour ago when the caller gives no timestamp.
            'completed_at' => $completedAt ?? now()->subHour(),
        ]);
    }
}
