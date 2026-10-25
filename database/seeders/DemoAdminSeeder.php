<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAdminSeeder extends Seeder
{
    /**
     * Demo questions as [key, area, subject, topic, prompt, options, correct, explanation, reference, source, status, verified, order].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: list<string>, 6: int, 7: string|null, 8: string|null, 9: string|null, 10: string, 11: bool, 12: int}>
     */
    private const QUESTIONS = [
        [
            'punctuation',
            'gen_ed', 'English', 'Grammar and usage',
            'Choose the sentence that is punctuated correctly.',
            [
                'The teacher asked, "Have you finished your project?"',
                'The teacher asked "Have you finished your project?"',
                'The teacher asked, Have you finished your project?',
                'The teacher asked: "have you finished your project?"',
            ],
            0,
            'A direct quotation is set off by a comma and enclosed in quotation marks, with the first word of the quotation capitalised.',
            'Rules on punctuating direct quotations.',
            'LET reviewer: English, 2023 edition',
            Question::STATUS_PUBLISHED, true, 1,
        ],
        [
            'adverb',
            'gen_ed', 'English', 'Grammar and usage',
            'In the sentence "The children played happily in the garden," what part of speech is the word "happily"?',
            ['Adjective', 'Adverb', 'Noun', 'Verb'],
            1,
            '"Happily" describes how the children played, so it modifies the verb and is an adverb.',
            'Parts of speech and their functions.',
            'LET reviewer: English, 2023 edition',
            Question::STATUS_PUBLISHED, true, 2,
        ],
        [
            'median',
            'gen_ed', 'Mathematics', 'Statistics and probability',
            'What is the median of the scores 7, 4, 9, 4, 12, 15, 4?',
            ['4', '7', '9', '12'],
            1,
            'Arranged in order the scores are 4, 4, 4, 7, 9, 12, 15. With seven scores, the fourth one is the median.',
            'Measures of central tendency for ungrouped data.',
            'LET reviewer: Mathematics, 2023 edition',
            Question::STATUS_PUBLISHED, false, 4,
        ],
        [
            'die',
            'gen_ed', 'Mathematics', 'Statistics and probability',
            'A fair die is rolled once. What is the probability of getting an even number?',
            ['1/6', '1/3', '1/2', '2/3'],
            2,
            'Three of the six outcomes are even (2, 4, and 6), so the probability is 3/6, or 1/2.',
            'Basic probability of equally likely outcomes.',
            'LET reviewer: Mathematics, 2023 edition',
            Question::STATUS_PUBLISHED, false, 5,
        ],
        [
            'outlier',
            'prof_ed', 'Assessment in Learning', 'Item analysis and grading',
            'Which measure of central tendency is most affected by an extremely high score?',
            ['Mean', 'Median', 'Mode', 'Range'],
            0,
            'The mean takes every score into account, so a single extreme score pulls it noticeably.',
            'Choosing the right measure of central tendency.',
            'LET reviewer: Professional Education, 2023 edition',
            Question::STATUS_PUBLISHED, true, 1,
        ],
        [
            'ethics',
            'prof_ed', 'The Teaching Profession', 'The Code of Ethics for Professional Teachers',
            'Under the Code of Ethics for Professional Teachers, how should a teacher deal with learners?',
            [
                'Treat every learner impartially and keep a professional relationship',
                'Accept gifts in exchange for higher grades',
                'Release learners\' records to anyone who asks for them',
                'Use the classroom to promote personal political views',
            ],
            0,
            'The Code requires teachers to uphold the dignity of the profession and to deal with learners with fairness and impartiality.',
            'Article VIII, The Code of Ethics for Professional Teachers.',
            'LET reviewer: Professional Education, 2023 edition',
            Question::STATUS_PUBLISHED, true, 2,
        ],
        [
            'personification',
            'specialization', 'English', 'English literature',
            'Which literary device is used in the line "The wind whispered through the trees"?',
            ['Simile', 'Metaphor', 'Personification', 'Hyperbole'],
            2,
            'Whispering is a human action given to the wind, which makes the line a personification.',
            'Figures of speech in poetry and prose.',
            null,
            Question::STATUS_PUBLISHED, false, 1,
        ],
        [
            'mars',
            'gen_ed', 'Science', 'General science',
            'Which planet in the solar system is known as the Red Planet?',
            ['Mercury', 'Venus', 'Mars', 'Jupiter'],
            2,
            'Iron oxide on the surface gives Mars its reddish colour.',
            null,
            null,
            Question::STATUS_DRAFT, false, 1,
        ],
        [
            'independence',
            'gen_ed', 'Social Studies', 'Philippine history',
            'In what year was Philippine independence proclaimed in Kawit, Cavite?',
            ['1896', '1898', '1901', '1935'],
            1,
            'The declaration was proclaimed on 12 June 1898.',
            'Philippine history timelines.',
            'LET reviewer: Social Studies, 2023 edition',
            Question::STATUS_ARCHIVED, false, 1,
        ],
    ];

    /**
     * Demo reports as [question key, reason, details, status, resolution note].
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: string, 4: string|null}>
     */
    private const REPORTS = [
        [
            'median',
            QuestionReport::REASON_WRONG_ANSWER,
            'I think the answer should be 4 because it appears most often.',
            QuestionReport::STATUS_OPEN,
            null,
        ],
        [
            'personification',
            QuestionReport::REASON_UNCLEAR,
            'The wording of the instructions confused me.',
            QuestionReport::STATUS_OPEN,
            null,
        ],
        [
            'punctuation',
            QuestionReport::REASON_TYPO,
            'The last choice starts with a small letter.',
            QuestionReport::STATUS_RESOLVED,
            'Choice D capitalisation corrected and the item re-verified.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@letreviewer.test'],
            [
                'name' => 'Reviewer Admin',
                'password' => 'password123',
                'role' => User::ROLE_ADMIN,
            ],
        );

        $questions = [];

        foreach (self::QUESTIONS as $row) {
            [$key, $area, $subject, $topic, $prompt, $options, $correct, $explanation, $reference, $source, $status, $verified, $order] = $row;

            $questions[$key] = Question::updateOrCreate(
                ['prompt' => $prompt],
                [
                    'topic_id' => $this->topic($area, $subject, $topic)->id,
                    'options' => $options,
                    'correct_option' => $correct,
                    'explanation' => $explanation,
                    'reference' => $reference,
                    'source' => $source,
                    'status' => $status,
                    'sort_order' => $order,
                    'reviewed_by' => $verified ? $admin->id : null,
                    'verified_at' => $verified ? now() : null,
                ],
            );
        }

        $student = User::where('email', 'demo@letreviewer.test')->first();

        foreach (self::REPORTS as [$key, $reason, $details, $status, $note]) {
            $question = $questions[$key] ?? null;

            if ($question === null) {
                continue;
            }

            QuestionReport::updateOrCreate(
                ['question_id' => $question->id, 'reason' => $reason],
                [
                    'user_id' => $student?->id,
                    'details' => $details,
                    'status' => $status,
                    'resolution_note' => $note,
                    'resolved_by' => $status === QuestionReport::STATUS_OPEN ? null : $admin->id,
                    'resolved_at' => $status === QuestionReport::STATUS_OPEN ? null : now()->subDay(),
                ],
            );
        }
    }

    /**
     * Find a topic by its area and subject.
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
