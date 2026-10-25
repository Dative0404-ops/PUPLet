<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public function test_the_student_navigation_lists_every_section(): void
    {
        $this->actingAs($this->student())
            ->get('/dashboard')
            ->assertSeeInOrder([
                'Dashboard',
                'My Subjects',
                'Practice Quiz',
                'My Progress',
                'DSS Recommendations',
                'Study Planner',
                'Bookmarks',
                'Profile and Settings',
            ]);
    }

    public function test_the_admin_navigation_lists_every_section(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertSeeInOrder([
                'Dashboard',
                'Manage Questions',
                'Manage Subjects and Topics',
                'Users',
                'Reports',
                'Content Verification',
                'DSS Settings',
                'System Settings',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DSS recommendations
    |--------------------------------------------------------------------------
    */

    public function test_a_weakly_answered_topic_is_recommended_with_a_reason(): void
    {
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'General Education', 'Grammar and usage');
        $quiz = $this->quiz($topic);
        $this->question($topic, 'Which sentence is correct?');

        $this->attempt($student, $quiz, 10, 3);

        $this->actingAs($student)
            ->get('/recommendations')
            ->assertOk()
            ->assertSee('Grammar and usage')
            ->assertSee('Needs practice')
            ->assertSee('30% correct across 10 items')
            ->assertSee('Practise this topic');
    }

    public function test_an_unpractised_topic_is_recommended_as_not_started(): void
    {
        $student = $this->student();
        $this->topic('gen_ed', 'General Education', 'Assessment of learning');

        $this->actingAs($student)
            ->get('/recommendations')
            ->assertOk()
            ->assertSee('Assessment of learning')
            ->assertSee('Not started');
    }

    public function test_the_recommendation_page_shows_the_rules_in_force(): void
    {
        $this->actingAs($this->student())
            ->get('/recommendations')
            ->assertOk()
            ->assertSee('How this list is built')
            ->assertSee('below 70%')
            ->assertSee('Up to 6 topics are recommended at a time.');
    }

    /*
    |--------------------------------------------------------------------------
    | Practice quiz
    |--------------------------------------------------------------------------
    */

    public function test_a_student_can_start_a_practice_drill_from_the_practice_page(): void
    {
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'General Education', 'Grammar and usage');

        foreach (range(1, 3) as $index) {
            $this->question($topic, 'Item '.$index.'?');
        }

        $this->actingAs($student)
            ->get('/practice')
            ->assertOk()
            ->assertSee('Start a practice drill')
            ->assertSee('Grammar and usage');

        $this->actingAs($student)
            ->post('/practice', ['topic_id' => $topic->id, 'items' => 10])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $student->id,
            'total_items' => 3,
        ]);
    }

    public function test_a_drill_refuses_a_topic_from_another_track(): void
    {
        $student = $this->student();
        $topic = $this->topic('specialization', 'Filipino', 'Panitikan');

        $this->actingAs($student)
            ->post('/practice', ['topic_id' => $topic->id, 'items' => 10])
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | DSS settings
    |--------------------------------------------------------------------------
    */

    public function test_an_administrator_can_tune_the_recommendation_rules(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/dss-settings')
            ->assertOk()
            ->assertSee('DSS Settings');

        $this->actingAs($this->admin())
            ->put('/admin/dss-settings', [
                'min_items' => 8,
                'limit' => 3,
                'weak_threshold' => 60,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'dss.min_items', 'value' => '8']);
        $this->assertDatabaseHas('settings', ['key' => 'dss.limit', 'value' => '3']);
        $this->assertDatabaseHas('settings', ['key' => 'dss.weak_threshold', 'value' => '60']);
        $this->assertDatabaseHas('settings', ['key' => 'dss.include_untouched', 'value' => '0']);
    }

    public function test_a_student_cannot_open_the_dss_settings(): void
    {
        $this->actingAs($this->student())
            ->get('/admin/dss-settings')
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | System settings
    |--------------------------------------------------------------------------
    */

    public function test_an_administrator_can_change_the_site_wide_settings(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/system-settings')
            ->assertOk()
            ->assertSee('System Settings');

        $this->actingAs($this->admin())
            ->put('/admin/system-settings', [
                'name' => 'LET Reviewer PH',
                'support_email' => 'help@example.test',
                'support_phone' => '(02) 9000-0000',
                'support_hours' => 'Mon–Sat, 9:00 AM–6:00 PM',
                'default_daily_target_items' => 30,
                'default_practice_timer' => '25',
                'mock_time_limit_minutes' => 120,
                'registration_enabled' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'site.name', 'value' => 'LET Reviewer PH']);
        $this->assertDatabaseHas('settings', ['key' => 'mock.time_limit_minutes', 'value' => '120']);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('help@example.test')
            ->assertSee('(02) 9000-0000')
            ->assertSee('Mon–Sat, 9:00 AM–6:00 PM');
    }

    public function test_a_new_account_starts_with_the_configured_defaults(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/system-settings', [
                'name' => 'LET Reviewer',
                'support_email' => 'support@letreviewer.test',
                'support_phone' => '',
                'support_hours' => '',
                'default_daily_target_items' => 35,
                'default_practice_timer' => '25',
                'mock_time_limit_minutes' => 150,
                'registration_enabled' => '1',
            ]);

        auth()->logout();

        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new.student@example.test',
            'exam_type' => 'secondary',
            'specialization' => 'English',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.student@example.test',
            'daily_target_items' => 35,
            'practice_time_limit_minutes' => 25,
        ]);
    }

    public function test_registration_can_be_closed(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/system-settings', [
                'name' => 'LET Reviewer',
                'support_email' => 'support@letreviewer.test',
                'support_phone' => '',
                'support_hours' => '',
                'default_daily_target_items' => 50,
                'default_practice_timer' => 'quiz',
                'mock_time_limit_minutes' => 150,
                'registration_enabled' => '0',
            ]);

        auth()->logout();

        $this->get('/register')->assertRedirect(route('login'));

        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new.student@example.test',
            'exam_type' => 'secondary',
            'specialization' => 'English',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'new.student@example.test']);
    }

    public function test_the_mock_examination_follows_the_configured_time_limit(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/system-settings', [
                'name' => 'LET Reviewer',
                'support_email' => 'support@letreviewer.test',
                'support_phone' => '',
                'support_hours' => '',
                'default_daily_target_items' => 50,
                'default_practice_timer' => 'quiz',
                'mock_time_limit_minutes' => 30,
                'registration_enabled' => '1',
            ]);

        $student = $this->student();

        $limit = \App\Support\AttemptBuilder::timeLimitFor(
            $student,
            Quiz::create([
                'type' => Quiz::TYPE_MOCK,
                'title' => 'Mock examination',
                'item_count' => 150,
                'time_limit_minutes' => 150,
            ]),
        );

        $this->assertSame(30, $limit);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_ADMIN], $attributes));
    }

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_STUDENT,
            'exam_type' => 'secondary',
            'specialization' => 'English',
        ], $attributes));
    }

    private function topic(string $area, string $subject, string $topic): Topic
    {
        $subjectModel = Subject::firstOrCreate(
            ['area' => $area, 'name' => $subject],
            ['sort_order' => 1],
        );

        return Topic::firstOrCreate(
            ['subject_id' => $subjectModel->id, 'name' => $topic],
            ['sort_order' => 1],
        );
    }

    private function quiz(Topic $topic): Quiz
    {
        return Quiz::create([
            'topic_id' => $topic->id,
            'type' => Quiz::TYPE_PRACTICE,
            'title' => 'Practice set: '.$topic->name,
            'item_count' => 20,
            'time_limit_minutes' => 25,
        ]);
    }

    private function question(Topic $topic, string $prompt): Question
    {
        return Question::create([
            'topic_id' => $topic->id,
            'prompt' => $prompt,
            'options' => ['Alpha', 'Beta', 'Gamma', 'Delta'],
            'correct_option' => 0,
            'explanation' => 'Explanation.',
            'status' => Question::STATUS_PUBLISHED,
            'sort_order' => 0,
        ]);
    }

    private function attempt(User $student, Quiz $quiz, int $items, int $correct): QuizAttempt
    {
        return QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'total_items' => $items,
            'answered_items' => $items,
            'score' => $correct,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
