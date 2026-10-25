<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudyGoal;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoStudentSeeder extends Seeder
{
    /**
     * Completed attempts as [area, subject, topic, items, correct, days ago, hour].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int, 4: int, 5: int, 6: int}>
     */
    private const ATTEMPTS = [
        ['gen_ed', 'English', 'Grammar and usage', 20, 15, 0, 9],
        ['specialization', 'English', 'English literature', 20, 17, 1, 20],
        ['gen_ed', 'Mathematics', 'Statistics and probability', 40, 18, 2, 19],
        ['prof_ed', 'Assessment in Learning', 'Item analysis and grading', 20, 11, 3, 18],
        ['prof_ed', 'Facilitating Learner-Centered Teaching', 'Learning theories', 20, 12, 4, 20],
        ['gen_ed', 'Mathematics', 'Fundamentals of mathematics', 20, 13, 5, 19],
        ['gen_ed', 'English', 'Reading comprehension', 20, 17, 6, 21],
        ['prof_ed', 'The Teaching Profession', 'The Code of Ethics for Professional Teachers', 20, 18, 7, 20],
        ['prof_ed', 'Assessment in Learning', 'Constructing assessment tools', 20, 14, 8, 19],
        ['gen_ed', 'Science', 'Earth science', 20, 12, 9, 17],
        ['specialization', 'English', 'Language and linguistics', 20, 14, 10, 20],
        ['gen_ed', 'Filipino', 'Panitikang Filipino', 20, 16, 11, 18],
        ['prof_ed', 'Child and Adolescent Learners and Learning Principles', 'Theories of development', 20, 15, 12, 19],
    ];

    /**
     * Lesson progress as [area, subject, topic, progress, days ago, completed].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int, 4: int, 5: bool}>
     */
    private const LESSONS = [
        ['prof_ed', 'Assessment in Learning', 'Item analysis and grading', 45, 1, false],
        ['specialization', 'English', 'English literature', 100, 1, true],
        ['prof_ed', 'Facilitating Learner-Centered Teaching', 'Learning theories', 100, 4, true],
        ['gen_ed', 'Mathematics', 'Statistics and probability', 30, 2, false],
        ['prof_ed', 'The Teaching Profession', 'The Code of Ethics for Professional Teachers', 100, 7, true],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student = User::updateOrCreate(
            ['email' => 'demo@letreviewer.test'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => 'password123',
                'exam_type' => 'secondary',
                'specialization' => 'English',
            ],
        );

        if (QuizAttempt::where('user_id', $student->id)->exists()) {
            return;
        }

        $this->seedAttempts($student);
        $this->seedLessons($student);
        $this->seedGoals($student);
    }

    /**
     * Create the completed attempts, one unfinished test, and the study activity records.
     */
    private function seedAttempts(User $student): void
    {
        foreach (self::ATTEMPTS as [$area, $subject, $topic, $items, $correct, $daysAgo, $hour]) {
            $startedAt = $daysAgo === 0
                ? now()->subMinutes(90)
                : now()->subDays($daysAgo)->setTime($hour, 0);

            QuizAttempt::create([
                'user_id' => $student->id,
                'quiz_id' => $this->quiz($area, $subject, $topic)->id,
                'total_items' => $items,
                'answered_items' => $items,
                'score' => $correct,
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addMinutes(22),
            ]);
        }

        QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $this->quiz('prof_ed', 'The Teacher and the School Curriculum', 'Curriculum design')->id,
            'total_items' => 20,
            'answered_items' => 8,
            'score' => 6,
            'started_at' => now()->subMinutes(40),
            'completed_at' => null,
        ]);
    }

    /**
     * Create the lesson progress records.
     */
    private function seedLessons(User $student): void
    {
        foreach (self::LESSONS as [$area, $subject, $topic, $progress, $daysAgo, $completed]) {
            $lesson = $this->lesson($area, $subject, $topic);

            LessonProgress::updateOrCreate(
                ['user_id' => $student->id, 'lesson_id' => $lesson->id],
                [
                    'progress' => $progress,
                    'last_viewed_at' => now()->subDays($daysAgo),
                    'completed_at' => $completed ? now()->subDays($daysAgo) : null,
                ],
            );
        }
    }

    /**
     * Create today's target and the upcoming review sessions.
     */
    private function seedGoals(User $student): void
    {
        StudyGoal::updateOrCreate(
            ['user_id' => $student->id, 'type' => StudyGoal::TYPE_DAILY_TARGET, 'target_date' => now()->toDateString()],
            ['title' => 'Answer 50 review items', 'target_items' => 50],
        );

        StudyGoal::updateOrCreate(
            [
                'user_id' => $student->id,
                'type' => StudyGoal::TYPE_REVIEW_SESSION,
                'target_date' => now()->addDay()->toDateString(),
            ],
            ['title' => 'Timed drill: Assessment in Learning', 'target_time' => '19:00'],
        );

        StudyGoal::updateOrCreate(
            [
                'user_id' => $student->id,
                'type' => StudyGoal::TYPE_REVIEW_SESSION,
                'target_date' => now()->addDays(3)->toDateString(),
            ],
            ['title' => 'Mock exam: General Education', 'target_time' => '09:00'],
        );
    }

    /**
     * Find the practice quiz of a topic.
     */
    private function quiz(string $area, string $subject, string $topic): Quiz
    {
        return $this->topic($area, $subject, $topic)->quizzes()->firstOrFail();
    }

    /**
     * Find the review lesson of a topic.
     */
    private function lesson(string $area, string $subject, string $topic): Lesson
    {
        return $this->topic($area, $subject, $topic)->lessons()->firstOrFail();
    }

    /**
     * Find a topic by its subject and area.
     */
    private function topic(string $area, string $subject, string $topic): Topic
    {
        return Subject::where('area', $area)
            ->where('name', $subject)
            ->firstOrFail()
            ->topics()
            ->where('name', $topic)
            ->firstOrFail();
    }
}
