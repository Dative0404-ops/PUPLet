<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\AttemptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Access control
    |--------------------------------------------------------------------------
    */

    public function test_the_admin_area_is_hidden_from_guests_and_students(): void
    {
        $this->get('/admin')->assertNotFound();

        $this->actingAs($this->student())
            ->get('/admin')
            ->assertNotFound();

        $this->actingAs($this->student())
            ->get('/admin/questions')
            ->assertNotFound();

        $this->actingAs($this->student())
            ->get('/admin/users')
            ->assertNotFound();
    }

    public function test_a_suspended_administrator_is_turned_away(): void
    {
        $admin = $this->admin(['suspended_at' => now()]);

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        $student = $this->student(['email' => 'blocked@example.test', 'suspended_at' => now()]);

        $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_header_shows_the_admin_link_only_to_administrators(): void
    {
        $link = route('admin.dashboard');

        $this->actingAs($this->admin())->get('/dashboard')->assertSee($link);
        $this->actingAs($this->student())->get('/dashboard')->assertDontSee($link);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin dashboard
    |--------------------------------------------------------------------------
    */

    public function test_the_dashboard_summarises_the_platform(): void
    {
        $admin = $this->admin();
        $this->student();
        $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'A live question');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Platform overview')
            ->assertSee('Question bank')
            ->assertSee('Platform activity')
            ->assertSee('General Education');
    }

    /*
    |--------------------------------------------------------------------------
    | Question management
    |--------------------------------------------------------------------------
    */

    public function test_the_question_list_can_be_filtered(): void
    {
        $admin = $this->admin();
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->question($topic, 'Which sentence is punctuated correctly?');
        $this->question($topic, 'What is the median of 1, 2, 3?', ['status' => Question::STATUS_ARCHIVED]);

        $this->actingAs($admin)
            ->get('/admin/questions?search=median')
            ->assertOk()
            ->assertSee('What is the median of 1, 2, 3?')
            ->assertDontSee('Which sentence is punctuated correctly?');

        $this->actingAs($admin)
            ->get('/admin/questions?status=archived')
            ->assertOk()
            ->assertSee('What is the median of 1, 2, 3?')
            ->assertDontSee('Which sentence is punctuated correctly?');
    }

    public function test_an_administrator_can_write_a_question(): void
    {
        $admin = $this->admin();
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->actingAs($admin)
            ->post('/admin/questions', [
                'topic_id' => $topic->id,
                'prompt' => 'What is a pronoun?',
                'options' => ['A word that replaces a noun', 'A describing word', '', ''],
                'correct_option' => 0,
                'explanation' => 'It stands in place of a noun.',
                'reference' => 'Parts of speech.',
                'source' => 'LET reviewer: English',
                'status' => Question::STATUS_PUBLISHED,
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::where('prompt', 'What is a pronoun?')->firstOrFail();

        $this->assertSame(['A word that replaces a noun', 'A describing word'], $question->options);
        $this->assertSame(0, $question->correct_option);
        $this->assertSame('published', $question->status);
    }

    public function test_the_answer_key_follows_the_choice_when_blank_rows_are_dropped(): void
    {
        $admin = $this->admin();
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->actingAs($admin)->post('/admin/questions', [
            'topic_id' => $topic->id,
            'prompt' => 'A question with gaps',
            'options' => ['', 'Second choice', '', 'Fourth choice'],
            'correct_option' => 3,
            'status' => Question::STATUS_PUBLISHED,
        ])->assertRedirect(route('admin.questions.index'));

        $question = Question::where('prompt', 'A question with gaps')->firstOrFail();

        $this->assertSame(['Second choice', 'Fourth choice'], $question->options);
        $this->assertSame(1, $question->correct_option);
    }

    public function test_a_question_needs_at_least_two_choices(): void
    {
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->actingAs($this->admin())
            ->post('/admin/questions', [
                'topic_id' => $topic->id,
                'prompt' => 'A broken item',
                'options' => ['Only one choice', '', '', ''],
                'correct_option' => 0,
                'status' => Question::STATUS_PUBLISHED,
            ])
            ->assertSessionHasErrors('options');

        $this->assertDatabaseMissing('questions', ['prompt' => 'A broken item']);
    }

    public function test_a_question_needs_a_correct_choice_that_exists(): void
    {
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->actingAs($this->admin())
            ->post('/admin/questions', [
                'topic_id' => $topic->id,
                'prompt' => 'An item without an answer',
                'options' => ['First choice', 'Second choice', '', ''],
                'correct_option' => 3,
                'status' => Question::STATUS_PUBLISHED,
            ])
            ->assertSessionHasErrors('correct_option');

        $this->assertDatabaseMissing('questions', ['prompt' => 'An item without an answer']);
    }

    public function test_a_question_can_be_edited(): void
    {
        $admin = $this->admin();
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');
        $question = $this->question($topic, 'The original prompt');

        $this->actingAs($admin)
            ->put("/admin/questions/{$question->id}", [
                'topic_id' => $topic->id,
                'prompt' => 'The corrected prompt',
                'options' => ['Alpha', 'Beta', 'Gamma'],
                'correct_option' => 2,
                'explanation' => 'Because gamma is right.',
                'status' => Question::STATUS_PUBLISHED,
            ])
            ->assertRedirect(route('admin.questions.edit', $question));

        $question->refresh();

        $this->assertSame('The corrected prompt', $question->prompt);
        $this->assertSame(2, $question->correct_option);
        $this->assertSame('Because gamma is right.', $question->explanation);
    }

    public function test_editing_a_question_clears_its_verification(): void
    {
        $admin = $this->admin();
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');
        $question = $this->question($topic, 'A verified item');

        $this->actingAs($admin)->post("/admin/questions/{$question->id}/verify")->assertRedirect();

        $this->assertNotNull($question->fresh()->verified_at);

        $this->actingAs($admin)->put("/admin/questions/{$question->id}", [
            'topic_id' => $topic->id,
            'prompt' => 'The content changed',
            'options' => ['Alpha', 'Beta'],
            'correct_option' => 0,
            'status' => Question::STATUS_PUBLISHED,
        ])->assertRedirect();

        $question->refresh();

        $this->assertNull($question->verified_at);
        $this->assertNull($question->reviewed_by);
    }

    public function test_a_question_can_be_archived_and_published_again(): void
    {
        $admin = $this->admin();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'An item to shelve');

        $this->actingAs($admin)->post("/admin/questions/{$question->id}/archive")->assertRedirect();
        $this->assertSame('archived', $question->fresh()->status);

        $this->actingAs($admin)->post("/admin/questions/{$question->id}/restore")->assertRedirect();
        $this->assertSame('published', $question->fresh()->status);
    }

    public function test_an_administrator_can_verify_a_question(): void
    {
        $admin = $this->admin();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'An item to check');

        $this->actingAs($admin)->post("/admin/questions/{$question->id}/verify")->assertRedirect();

        $question->refresh();

        $this->assertNotNull($question->verified_at);
        $this->assertSame($admin->id, $question->reviewed_by);
        $this->assertTrue($question->isVerified());
    }

    public function test_an_archived_question_is_kept_out_of_the_reviewer(): void
    {
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->question($topic, 'A live item');
        $this->question($topic, 'A shelved item', ['status' => Question::STATUS_ARCHIVED]);

        $quiz = $topic->quizzes()->create([
            'type' => Quiz::TYPE_PRACTICE,
            'title' => 'Practice set: Grammar and usage',
            'item_count' => 20,
            'time_limit_minutes' => 25,
        ]);

        $served = AttemptBuilder::poolForQuiz($quiz)->pluck('prompt')->all();

        $this->assertContains('A live item', $served);
        $this->assertNotContains('A shelved item', $served);
    }

    public function test_an_administrator_can_delete_a_question(): void
    {
        $admin = $this->admin();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'An item to remove');

        $this->actingAs($admin)
            ->delete("/admin/questions/{$question->id}")
            ->assertRedirect(route('admin.questions.index'));

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Subject and topic management
    |--------------------------------------------------------------------------
    */

    public function test_an_administrator_can_add_a_subject(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/catalog/subjects', ['area' => 'gen_ed', 'name' => 'Music and Arts'])
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', ['area' => 'gen_ed', 'name' => 'Music and Arts']);
    }

    public function test_a_subject_name_must_be_unique_inside_its_area(): void
    {
        $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->actingAs($this->admin())
            ->post('/admin/catalog/subjects', ['area' => 'gen_ed', 'name' => 'English'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin())
            ->post('/admin/catalog/subjects', ['area' => 'prof_ed', 'name' => 'English'])
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', ['area' => 'prof_ed', 'name' => 'English']);
    }

    public function test_a_subject_holding_topics_cannot_be_deleted(): void
    {
        $subject = Subject::create(['area' => 'gen_ed', 'name' => 'English', 'sort_order' => 1]);
        Topic::create(['subject_id' => $subject->id, 'name' => 'Grammar and usage', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->delete("/admin/catalog/subjects/{$subject->id}")
            ->assertSessionHasErrors('catalog');

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_an_empty_subject_can_be_deleted(): void
    {
        $subject = Subject::create(['area' => 'gen_ed', 'name' => 'Music and Arts', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->delete("/admin/catalog/subjects/{$subject->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_an_administrator_can_add_rename_and_remove_a_topic(): void
    {
        $admin = $this->admin();
        $subject = Subject::create(['area' => 'gen_ed', 'name' => 'English', 'sort_order' => 1]);

        $this->actingAs($admin)
            ->post('/admin/catalog/topics', ['subject_id' => $subject->id, 'name' => 'Grammar and usage'])
            ->assertRedirect();

        $topic = Topic::where('name', 'Grammar and usage')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/catalog/topics/{$topic->id}", ['subject_id' => $subject->id, 'name' => 'Grammar'])
            ->assertRedirect();

        $this->assertSame('Grammar', $topic->fresh()->name);

        $this->actingAs($admin)
            ->delete("/admin/catalog/topics/{$topic->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('topics', ['id' => $topic->id]);
    }

    public function test_a_topic_holding_questions_cannot_be_deleted(): void
    {
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');
        $this->question($topic, 'A question in the way');

        $this->actingAs($this->admin())
            ->delete("/admin/catalog/topics/{$topic->id}")
            ->assertSessionHasErrors('catalog');

        $this->assertDatabaseHas('topics', ['id' => $topic->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Content verification
    |--------------------------------------------------------------------------
    */

    public function test_the_verification_queue_filters_by_verification_state(): void
    {
        $admin = $this->admin();
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $checked = $this->question($topic, 'An item already checked', [
            'reviewed_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $waiting = $this->question($topic, 'An item still waiting');

        $this->actingAs($admin)
            ->get('/admin/verification')
            ->assertOk()
            ->assertSee('An item still waiting')
            ->assertDontSee('An item already checked');

        $this->actingAs($admin)
            ->get('/admin/verification?filter=verified')
            ->assertOk()
            ->assertSee('An item already checked')
            ->assertDontSee('An item still waiting');
    }

    public function test_the_verification_queue_finds_published_items_without_an_explanation(): void
    {
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $this->question($topic, 'An item with no explanation', ['explanation' => null]);

        $this->actingAs($this->admin())
            ->get('/admin/verification?filter=unexplained')
            ->assertOk()
            ->assertSee('An item with no explanation');
    }

    /*
    |--------------------------------------------------------------------------
    | User management
    |--------------------------------------------------------------------------
    */

    public function test_the_user_list_can_be_searched_and_filtered(): void
    {
        $admin = $this->admin(['name' => 'Reviewer Admin']);
        $this->student(['name' => 'Maria Santos', 'email' => 'maria@example.test']);

        $this->actingAs($admin)
            ->get('/admin/users?search=maria')
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertDontSee('Reviewer Admin');

        $this->actingAs($admin)
            ->get('/admin/users?role=admin')
            ->assertOk()
            ->assertSee('Reviewer Admin')
            ->assertDontSee('maria@example.test');
    }

    public function test_the_user_detail_page_shows_the_account(): void
    {
        $admin = $this->admin();
        $student = $this->student(['name' => 'Maria Santos']);

        $this->actingAs($admin)
            ->get("/admin/users/{$student->id}")
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertSee('Secondary (BSED) - English')
            ->assertSee('Suspend this account');
    }

    public function test_an_administrator_can_promote_and_demote_an_account(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($admin)
            ->put("/admin/users/{$student->id}/role", ['role' => User::ROLE_ADMIN])
            ->assertRedirect();

        $this->assertTrue($student->fresh()->isAdmin());

        $this->actingAs($admin)
            ->put("/admin/users/{$student->id}/role", ['role' => User::ROLE_STUDENT])
            ->assertRedirect();

        $this->assertFalse($student->fresh()->isAdmin());
    }

    public function test_an_administrator_cannot_change_their_own_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/admin/users/{$admin->id}/role", ['role' => User::ROLE_STUDENT])
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_an_administrator_can_suspend_and_reactivate_an_account(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($admin)
            ->post("/admin/users/{$student->id}/suspend")
            ->assertRedirect();

        $this->assertTrue($student->fresh()->isSuspended());

        $this->actingAs($admin)
            ->post("/admin/users/{$student->id}/reactivate")
            ->assertRedirect();

        $this->assertFalse($student->fresh()->isSuspended());
    }

    public function test_an_administrator_cannot_suspend_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/admin/users/{$admin->id}/suspend")
            ->assertForbidden();

        $this->assertFalse($admin->fresh()->isSuspended());
    }

    /*
    |--------------------------------------------------------------------------
    | Question reports
    |--------------------------------------------------------------------------
    */

    public function test_a_student_can_report_a_question(): void
    {
        $student = $this->student();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'A doubtful item');

        $this->actingAs($student)
            ->post("/review/questions/{$question->id}/report", [
                'reason' => QuestionReport::REASON_WRONG_ANSWER,
                'details' => 'The key looks wrong.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('question_reports', [
            'question_id' => $question->id,
            'user_id' => $student->id,
            'reason' => 'wrong_answer',
            'status' => 'open',
        ]);
    }

    public function test_a_student_cannot_report_the_same_question_twice(): void
    {
        $student = $this->student();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'A doubtful item');

        $payload = ['reason' => QuestionReport::REASON_UNCLEAR];

        $this->actingAs($student)->post("/review/questions/{$question->id}/report", $payload)->assertRedirect();
        $this->actingAs($student)->post("/review/questions/{$question->id}/report", $payload)->assertRedirect();

        $this->assertSame(1, QuestionReport::where('question_id', $question->id)->count());
    }

    public function test_a_student_cannot_report_a_question_outside_their_track(): void
    {
        $student = $this->student(['exam_type' => 'secondary', 'specialization' => 'English']);
        $question = $this->question($this->topic('specialization', 'Filipino', 'Wika at gramatika'), 'Another major');

        $this->actingAs($student)
            ->post("/review/questions/{$question->id}/report", ['reason' => QuestionReport::REASON_OTHER])
            ->assertNotFound();

        $this->assertDatabaseCount('question_reports', 0);
    }

    public function test_a_report_needs_a_known_reason(): void
    {
        $student = $this->student();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'A doubtful item');

        $this->actingAs($student)
            ->post("/review/questions/{$question->id}/report", ['reason' => 'made-up'])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('question_reports', 0);
    }

    public function test_the_report_queue_can_be_filtered_by_status(): void
    {
        $admin = $this->admin();
        $topic = $this->topic('gen_ed', 'English', 'Grammar and usage');

        $open = $this->question($topic, 'An item with an open report');
        $closed = $this->question($topic, 'An item with a closed report');

        QuestionReport::create([
            'question_id' => $open->id,
            'reason' => QuestionReport::REASON_WRONG_ANSWER,
            'status' => QuestionReport::STATUS_OPEN,
        ]);

        QuestionReport::create([
            'question_id' => $closed->id,
            'reason' => QuestionReport::REASON_TYPO,
            'status' => QuestionReport::STATUS_DISMISSED,
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports?status=open')
            ->assertOk()
            ->assertSee('An item with an open report')
            ->assertDontSee('An item with a closed report');

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('An item with an open report')
            ->assertSee('An item with a closed report');
    }

    public function test_an_administrator_can_resolve_a_report(): void
    {
        $admin = $this->admin();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'A reported item');

        $report = QuestionReport::create([
            'question_id' => $question->id,
            'reason' => QuestionReport::REASON_WRONG_ANSWER,
            'status' => QuestionReport::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->post("/admin/reports/{$report->id}/resolve", ['resolution_note' => 'Answer key corrected.'])
            ->assertRedirect();

        $report->refresh();

        $this->assertSame('resolved', $report->status);
        $this->assertSame('Answer key corrected.', $report->resolution_note);
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertNotNull($report->resolved_at);
    }

    public function test_an_administrator_can_dismiss_a_report(): void
    {
        $admin = $this->admin();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'A reported item');

        $report = QuestionReport::create([
            'question_id' => $question->id,
            'reason' => QuestionReport::REASON_UNCLEAR,
            'status' => QuestionReport::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->post("/admin/reports/{$report->id}/dismiss", ['resolution_note' => 'The item is correct as written.'])
            ->assertRedirect();

        $this->assertSame('dismissed', $report->fresh()->status);
    }

    public function test_a_closed_report_is_left_alone(): void
    {
        $admin = $this->admin();
        $question = $this->question($this->topic('gen_ed', 'English', 'Grammar and usage'), 'A reported item');

        $report = QuestionReport::create([
            'question_id' => $question->id,
            'reason' => QuestionReport::REASON_TYPO,
            'status' => QuestionReport::STATUS_RESOLVED,
        ]);

        $this->actingAs($admin)
            ->post("/admin/reports/{$report->id}/dismiss", ['resolution_note' => 'Too late.'])
            ->assertRedirect();

        $report->refresh();

        $this->assertSame('resolved', $report->status);
        $this->assertNull($report->resolution_note);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Create an administrator account.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_ADMIN], $attributes));
    }

    /**
     * Create a student with a LET track.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_STUDENT,
            'exam_type' => 'secondary',
            'specialization' => 'English',
        ], $attributes));
    }

    /**
     * Find or create a topic, with its subject.
     */
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

    /**
     * Write a single multiple-choice question into a topic.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function question(Topic $topic, string $prompt, array $attributes = []): Question
    {
        return Question::create(array_merge([
            'topic_id' => $topic->id,
            'prompt' => $prompt,
            'options' => ['Alpha', 'Beta', 'Gamma', 'Delta'],
            'correct_option' => 0,
            'explanation' => 'Explanation.',
            'status' => Question::STATUS_PUBLISHED,
            'sort_order' => 0,
        ], $attributes));
    }
}
