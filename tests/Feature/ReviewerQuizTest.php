<?php

// Feature tests live here; this class covers the study reviewer and quizzes.
namespace Tests\Feature;

// Eloquent model for a topic's reviewer lesson.
use App\Models\Lesson;
// Eloquent model tracking a student's progress through a lesson.
use App\Models\LessonProgress;
// Eloquent model for a question bank item.
use App\Models\Question;
// Eloquent model for a quiz definition.
use App\Models\Quiz;
// Eloquent model for one recorded quiz sitting.
use App\Models\QuizAttempt;
// Eloquent model for a subject (LET area bucket).
use App\Models\Subject;
// Eloquent model for a topic inside a subject.
use App\Models\Topic;
// Eloquent model for an account.
use App\Models\User;
// The seeder that builds the sample LET catalog.
use Database\Seeders\LetCatalogSeeder;
// RefreshDatabase gives every test a fresh database.
use Illuminate\Foundation\Testing\RefreshDatabase;
// Our shared base TestCase that boots the Laravel application.
use Tests\TestCase;

// Covers subject/topic browsing, lessons, practice quizzes, the timer and mocks.
class ReviewerQuizTest extends TestCase
{
    // Refreshes the database before each test so runs do not interfere.
    use RefreshDatabase;

    // Protects that the reviewer pages require a signed-in user.
    public function test_reviewer_pages_require_authentication(): void
    {
        // A guest is redirected to /login from the reviewer landing page.
        $this->get('/review')->assertRedirect('/login');
        // The mock-exam page is likewise protected.
        $this->get('/mock-exam')->assertRedirect('/login');
        // The bookmarks page is likewise protected.
        $this->get('/bookmarks')->assertRedirect('/login');
    }

    // Checks the subject picker lists only the subjects in the student's track.
    public function test_subject_selection_lists_the_subjects_of_the_track(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // A Secondary - English student.
        $student = $this->student();

        // The picker shows the three areas and the student's own course/track.
        $this->actingAs($student)
            ->get('/review')
            ->assertOk()
            ->assertSee('Choose a subject to review')
            ->assertSee('General Education')
            ->assertSee('Professional Education')
            ->assertSee('Specialization')
            ->assertSee('Understanding the Self')
            ->assertSee('Secondary (BSED) - English')
            // Another major's subject must be hidden by the track gate.
            ->assertDontSee('Values Education');
    }

    // Protects that elementary students never see specialization subjects.
    public function test_elementary_students_do_not_see_specialization_subjects(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // An Elementary (BEED) - MAPEH student.
        $student = $this->student(['exam_type' => 'elementary', 'specialization' => 'MAPEH']);

        // Only the GenEd area is shown; the Specialization block is absent.
        $this->actingAs($student)
            ->get('/review')
            ->assertOk()
            ->assertSee('General Education')
            ->assertDontSee('Specialization');
    }

    // Checks a subject page lists its topics with their progress state.
    public function test_subject_page_lists_its_topics(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // A Secondary - English student.
        $student = $this->student();

        // Finds the General Education -> Purposive Communication in English subject.
        $subject = Subject::where('area', 'gen_ed')->where('name', 'Purposive Communication in English')->firstOrFail();

        // The subject page lists its topics and shows a "Not started" badge.
        $this->actingAs($student)
            ->get("/review/subjects/{$subject->id}")
            ->assertOk()
            ->assertSee('Purposive Communication in English')
            ->assertSee('Not started');
    }

    // Protects the track gate: a subject outside the track returns 404.
    public function test_a_subject_outside_the_students_track_is_not_found(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // A Secondary - English student.
        $student = $this->student(['exam_type' => 'secondary', 'specialization' => 'English']);

        // A specialization subject for a different major (Filipino).
        $outside = Subject::where('area', 'specialization')->where('name', 'Filipino')->firstOrFail();

        // Opening that subject is treated as if it did not exist.
        $this->actingAs($student)
            ->get("/review/subjects/{$outside->id}")
            ->assertNotFound();
    }

    // Checks the topic hub links to the reviewer material and practice quiz.
    public function test_topic_hub_links_the_material_and_the_practice_quiz(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student and the topic to open.
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');

        // The hub names the topic and links its material, quiz and the mock exam.
        $this->actingAs($student)
            ->get("/review/topics/{$topic->id}")
            ->assertOk()
            ->assertSee('Purposive Communication in English')
            ->assertSee('Reviewer material')
            ->assertSee('Practice quiz')
            // An empty question bank is flagged on the hub.
            ->assertSee('no questions yet')
            ->assertSee('Take the mock examination');
    }

    // Checks the lesson page renders the structured reviewer material.
    public function test_lesson_page_shows_the_structured_reviewer_material(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student and the lesson to open.
        $student = $this->student();
        $lesson = $this->lesson('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');

        // Fills the lesson with one of each structured content block.
        $lesson->update([
            'summary' => 'A short look at subject-verb agreement.',
            'key_terms' => [['term' => 'Concord', 'definition' => 'Agreement between subject and verb.']],
            'examples' => [['title' => 'Worked example 1', 'body' => 'The dogs bark.']],
            'study_notes' => 'Watch out for collective nouns.',
        ]);

        // Each section and its content should be rendered on the page.
        $this->actingAs($student)
            ->get("/review/lessons/{$lesson->id}")
            ->assertOk()
            ->assertSee('Summary')
            ->assertSee('A short look at subject-verb agreement.')
            ->assertSee('Key terms and definitions')
            ->assertSee('Concord')
            ->assertSee('Worked examples')
            ->assertSee('Worked example 1')
            ->assertSee('Study notes')
            ->assertSee('Watch out for collective nouns.');

        // Opening the lesson also records lesson progress for the student.
        $this->assertDatabaseHas('lesson_progresses', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    // Checks a lesson can be marked as reviewed, storing 100% progress.
    public function test_a_lesson_can_be_marked_as_reviewed(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student and the lesson to complete.
        $student = $this->student();
        $lesson = $this->lesson('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');

        // Posts the "mark as reviewed" action, which redirects back to the lesson.
        $this->actingAs($student)
            ->post("/review/lessons/{$lesson->id}/complete")
            ->assertRedirect("/review/lessons/{$lesson->id}");

        // Fetches the created progress row.
        $progress = LessonProgress::where('user_id', $student->id)->where('lesson_id', $lesson->id)->firstOrFail();

        // Completion stores 100 percent and a completion timestamp.
        $this->assertSame(100, $progress->progress);
        $this->assertNotNull($progress->completed_at);

        // Re-opening the lesson now shows the reviewed confirmation.
        $this->actingAs($student)
            ->get("/review/lessons/{$lesson->id}")
            ->assertOk()
            ->assertSee('You have marked this topic as reviewed.');
    }

    // Protects the empty-bank behaviour of a practice quiz.
    public function test_a_practice_quiz_reports_an_empty_question_bank(): void
    {
        // Builds the sample catalog (topics/quizzes but no questions).
        $this->seedCatalog();

        // The student and the quiz to inspect.
        $student = $this->student();
        $quiz = $this->quiz('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');

        // The quiz page flags that there are no questions yet.
        $this->actingAs($student)
            ->get("/review/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertSee('no questions yet');

        // Trying to start it fails validation on the "quiz" field.
        $this->actingAs($student)
            ->from("/review/quizzes/{$quiz->id}")
            ->post("/review/quizzes/{$quiz->id}/attempts")
            ->assertSessionHasErrors('quiz');

        // Confirms no attempt row was created.
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    // Checks a practice attempt is graded and its results show explanations.
    public function test_a_practice_attempt_is_graded_and_shows_explanations(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student and the topic to practise.
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');

        // Adds three questions, each with its own explanation.
        $this->question($topic, 'Question one?', 'Explanation one.');
        $this->question($topic, 'Question two?', 'Explanation two.');
        $this->question($topic, 'Question three?', 'Explanation three.');

        // Narrows the topic's quiz to exactly three items.
        $quiz = $topic->quizzes()->firstOrFail();
        $quiz->update(['item_count' => 3]);

        // Starts the attempt; a blank answer row is created per question.
        $this->actingAs($student)->post("/review/quizzes/{$quiz->id}/attempts");

        // Loads the newest attempt with its answers and questions.
        $attempt = QuizAttempt::with('answers.question')->latest('id')->firstOrFail();
        // The attempt served all three questions.
        $this->assertSame(3, $attempt->total_items);

        // Re-keys the answer rows by their question prompt for easy lookup.
        $byPrompt = $attempt->answers->keyBy(fn ($answer) => $answer->question->prompt);

        // Submits two answers: the first is correct (index 0), the second wrong (2).
        $this->actingAs($student)
            ->post("/review/attempts/{$attempt->id}/submit", [
                'answers' => [
                    $byPrompt['Question one?']->question_id => 0,
                    $byPrompt['Question two?']->question_id => 2,
                ],
            ])
            // A successful submit redirects to the results page.
            ->assertRedirect("/review/attempts/{$attempt->id}/results");

        // Reloads the attempt to read the recomputed grading.
        $attempt->refresh();
        // One correct answer, two questions answered, and now completed.
        $this->assertSame(1, $attempt->score);
        $this->assertSame(2, $attempt->answered_items);
        $this->assertNotNull($attempt->completed_at);

        // The results page shows the score, percentage and both explanations.
        $this->actingAs($student)
            ->get("/review/attempts/{$attempt->id}/results")
            ->assertOk()
            ->assertSee('1 / 3')
            ->assertSee('33%')
            ->assertSee('Correct answers')
            ->assertSee('Incorrect answers')
            ->assertSee('Correct answer:')
            ->assertSee('Explanation two.')
            ->assertSee('Question three?');
    }

    // Checks the mistakes page lists only wrong items and can be retried.
    public function test_mistakes_only_list_the_incorrect_items_and_can_be_retried(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student and the topic to practise.
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');

        // Three questions: one to answer correctly, two to miss.
        $this->question($topic, 'Correct item?', 'Explanation one.');
        $this->question($topic, 'Missed item one?', 'Explanation two.');
        $this->question($topic, 'Missed item two?', 'Explanation three.');

        // Narrows the quiz to three items.
        $quiz = $topic->quizzes()->firstOrFail();
        $quiz->update(['item_count' => 3]);

        // Starts the attempt.
        $this->actingAs($student)->post("/review/quizzes/{$quiz->id}/attempts");

        // Loads the attempt and re-keys answers by question prompt.
        $attempt = QuizAttempt::with('answers.question')->latest('id')->firstOrFail();
        $byPrompt = $attempt->answers->keyBy(fn ($answer) => $answer->question->prompt);

        // Answers the correct item correctly and one other item wrongly.
        $this->actingAs($student)->post("/review/attempts/{$attempt->id}/submit", [
            'answers' => [
                $byPrompt['Correct item?']->question_id => 0,
                $byPrompt['Missed item one?']->question_id => 3,
            ],
        ]);

        // The mistakes page lists both missed items but not the correct one.
        $this->actingAs($student)
            ->get("/review/attempts/{$attempt->id}/mistakes")
            ->assertOk()
            ->assertSee('Missed item one?')
            ->assertSee('Missed item two?')
            // The correctly answered item is excluded from the mistakes list.
            ->assertDontSee('Correct item?');

        // Retrying with scope=mistakes redirects to the new attempt.
        $this->actingAs($student)
            ->post("/review/attempts/{$attempt->id}/retry", ['scope' => 'mistakes'])
            ->assertRedirect();

        // The newest attempt is the retry, a separate row serving only 2 questions.
        $retry = QuizAttempt::latest('id')->firstOrFail();

        $this->assertNotSame($attempt->id, $retry->id);
        $this->assertSame(2, $retry->total_items);
    }

    // Checks a question can be bookmarked and then unbookmarked.
    public function test_questions_can_be_bookmarked_and_removed(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student, the topic and the question to bookmark.
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');
        $question = $this->question($topic, 'A tricky item?', 'Because it is tricky.');

        // Bookmarking from the bookmarks page redirects back to it.
        $this->actingAs($student)
            ->from('/bookmarks')
            ->post("/review/questions/{$question->id}/bookmark")
            ->assertRedirect('/bookmarks');

        // Confirms a bookmark row now links the student to the question.
        $this->assertDatabaseHas('question_bookmarks', [
            'user_id' => $student->id,
            'question_id' => $question->id,
        ]);

        // The bookmarks page lists the saved question.
        $this->actingAs($student)
            ->get('/bookmarks')
            ->assertOk()
            ->assertSee('A tricky item?')
            ->assertSee('Saved');

        // Posting to the same endpoint again toggles the bookmark off.
        $this->actingAs($student)
            ->from('/bookmarks')
            ->post("/review/questions/{$question->id}/bookmark")
            ->assertRedirect('/bookmarks');

        // Confirms the bookmark row is now gone.
        $this->assertDatabaseMissing('question_bookmarks', [
            'user_id' => $student->id,
            'question_id' => $question->id,
        ]);
    }

    // Checks a timed attempt shows a countdown and the submit action.
    public function test_a_timed_attempt_shows_a_countdown(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student, topic and single question.
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');
        $this->question($topic, 'Timed item?', 'Explanation.');

        // Gives the quiz one item and a 25-minute limit.
        $quiz = $topic->quizzes()->firstOrFail();
        $quiz->update(['item_count' => 1, 'time_limit_minutes' => 25]);

        // Starts the timed attempt.
        $this->actingAs($student)->post("/review/quizzes/{$quiz->id}/attempts");

        // Loads the newest attempt.
        $attempt = QuizAttempt::latest('id')->firstOrFail();

        // The attempt screen shows a countdown, its data attribute and the submit button.
        $this->actingAs($student)
            ->get("/review/attempts/{$attempt->id}")
            ->assertOk()
            ->assertSee('Time remaining')
            // Passing false keeps the assertion from escaping the raw attribute.
            ->assertSee('data-remaining-seconds', false)
            ->assertSee('Submit answers');
    }

    // Protects the backend half of the timer: an overdue attempt auto-submits.
    public function test_a_timed_attempt_is_submitted_automatically_once_the_deadline_passes(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The student, topic and the single question.
        $student = $this->student();
        $topic = $this->topic('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');
        $question = $this->question($topic, 'Late item?', 'Explanation.');

        // Gives the quiz one item and a 5-minute limit.
        $quiz = $topic->quizzes()->firstOrFail();
        $quiz->update(['item_count' => 1, 'time_limit_minutes' => 5]);

        // Starts the timed attempt.
        $this->actingAs($student)->post("/review/quizzes/{$quiz->id}/attempts");

        // Loads the newest attempt.
        $attempt = QuizAttempt::latest('id')->firstOrFail();
        // Rewinds its start time 30 minutes so the 5-minute deadline has passed.
        $attempt->forceFill(['started_at' => now()->subMinutes(30)])->save();

        // Opening the overdue attempt re-scores it and redirects to the results.
        $this->actingAs($student)
            ->get("/review/attempts/{$attempt->id}")
            ->assertRedirect("/review/attempts/{$attempt->id}/results")
            // A flash message explains the automatic submission.
            ->assertSessionHas('status', 'Time was up, so your answers were submitted automatically.');

        // Reloads the attempt to check the automatic grading.
        $attempt->refresh();
        // The attempt is now closed with no score and no answers.
        $this->assertNotNull($attempt->completed_at);
        $this->assertSame(0, $attempt->score);
        $this->assertSame(0, $attempt->answered_items);

        // The unanswered question was still recorded as an incorrect answer row.
        $this->assertDatabaseHas('quiz_attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'is_correct' => false,
        ]);
    }

    // Protects that a student cannot open another student's attempt.
    public function test_a_student_cannot_open_another_students_attempt(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // The intruding student and the student who owns the attempt.
        $student = $this->student();
        $other = $this->student(['email' => 'other@example.test']);

        // The topic and its single question.
        $topic = $this->topic('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English');
        $this->question($topic, 'Private item?', 'Explanation.');

        // Narrows the topic's quiz to one item.
        $quiz = $topic->quizzes()->firstOrFail();
        $quiz->update(['item_count' => 1]);

        // The owner starts the attempt.
        $this->actingAs($other)->post("/review/quizzes/{$quiz->id}/attempts");

        // Loads that attempt.
        $attempt = QuizAttempt::latest('id')->firstOrFail();

        // The other student opening it gets a 404, as if it did not exist.
        $this->actingAs($student)
            ->get("/review/attempts/{$attempt->id}")
            ->assertNotFound();
    }

    // Checks the mock-exam page reports the shortfall and blocks starting.
    public function test_the_mock_exam_reports_the_shortfall_and_blocks_starting(): void
    {
        // Builds the sample catalog with no questions, so banks are empty.
        $this->seedCatalog();

        // A Secondary - English student.
        $student = $this->student();

        // The page shows the areas, the shortfall counts and the warning text.
        $this->actingAs($student)
            ->get('/mock-exam')
            ->assertOk()
            ->assertSee('Mock examination')
            ->assertSee('General Education')
            ->assertSee('Needs 20 more')
            ->assertSee('100 items')
            ->assertSee('does not hold enough items');

        // Trying to start the mock fails validation on the "quiz" field.
        $this->actingAs($student)
            ->from('/mock-exam')
            ->post('/mock-exam')
            ->assertSessionHasErrors('quiz');

        // Confirms no attempt row was created.
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    // Checks the mock exam starts once every area's bank is full enough.
    public function test_the_mock_exam_starts_when_the_bank_is_complete(): void
    {
        // Builds the sample catalog.
        $this->seedCatalog();

        // A Secondary - English student (BSED blueprint: 20 + 40 + 40 = 100).
        $student = $this->student();

        // Fills each area's bank to the exact count the blueprint needs.
        $this->fillBank('gen_ed', 'Purposive Communication in English', 'Purposive Communication in English', 20);
        $this->fillBank('prof_ed', 'The Professional Teacher', 'The Professional Teacher', 40);
        $this->fillBank('specialization', 'English', 'English literature', 40);

        // The page now reports the mock is ready and drops the warning.
        $this->actingAs($student)
            ->get('/mock-exam')
            ->assertOk()
            ->assertSee('Ready')
            ->assertDontSee('does not hold enough items');

        // Starting the mock redirects into the new attempt.
        $this->actingAs($student)
            ->post('/mock-exam')
            ->assertRedirect();

        // Loads the newly created attempt.
        $attempt = QuizAttempt::latest('id')->firstOrFail();

        // The attempt serves 100 items with one answer row per question.
        $this->assertSame(100, $attempt->total_items);
        $this->assertSame(100, $attempt->answers()->count());

        // The mock quiz is typed as a mock and uses the 150-minute default limit.
        $this->assertDatabaseHas('quizzes', [
            'id' => $attempt->quiz_id,
            'type' => 'mock',
            'time_limit_minutes' => 150,
        ]);
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
     * Find the reviewer lesson of a topic.
     */
    // Helper: return the reviewer lesson attached to a topic.
    private function lesson(string $area, string $subject, string $topic): Lesson
    {
        // Resolves the topic, then takes its first related lesson.
        return $this->topic($area, $subject, $topic)->lessons()->firstOrFail();
    }

    /**
     * Write a single multiple-choice question into a topic.
     */
    // Helper: insert one question, optionally choosing the correct option index.
    private function question(Topic $topic, string $prompt, ?string $explanation, int $correctOption = 0): Question
    {
        // Creates the question with four fixed options.
        return Question::create([
            'topic_id' => $topic->id,
            'prompt' => $prompt,
            'options' => ['Alpha', 'Beta', 'Gamma', 'Delta'],
            'correct_option' => $correctOption,
            'explanation' => $explanation,
            'sort_order' => 0,
        ]);
    }

    /**
     * Write a batch of questions into a topic, to fill the mock exam blueprint.
     */
    // Helper: create $count questions in a topic to satisfy the mock blueprint.
    private function fillBank(string $area, string $subject, string $topic, int $count): void
    {
        // Resolves the target topic first.
        $topicModel = $this->topic($area, $subject, $topic);

        // Creates $count distinct questions numbered 1..$count.
        for ($index = 1; $index <= $count; $index++) {
            Question::create([
                'topic_id' => $topicModel->id,
                // The topic name plus the item number keeps prompts unique.
                'prompt' => $topic.' item '.$index.'?',
                'options' => ['Alpha', 'Beta', 'Gamma', 'Delta'],
                'correct_option' => 0,
                'explanation' => 'Explanation for item '.$index.'.',
                'sort_order' => $index,
            ]);
        }
    }
}
