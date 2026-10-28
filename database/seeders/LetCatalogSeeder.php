<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class LetCatalogSeeder extends Seeder
{
    /**
     * The subjects and topics covered by the reviewer, grouped by LET area.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const CATALOG = [
        'gen_ed' => [
            'Purposive Communication in English' => [
                'Purposive Communication in English',
            ],
            'Malayuning Komunikasyon' => [
                'Malayuning Komunikasyon',
            ],
            'Science and Technology' => [
                'Science and Technology',
            ],
            'Mathematics in the Modern World' => [
                'Mathematics in the Modern World',
            ],
            'Readings in Philippine History and Society' => [
                'Readings in Philippine History and Society',
            ],
            'The Life and Works of Rizal' => [
                'The Life and Works of Rizal',
            ],
            'Arts Appreciation' => [
                'Arts Appreciation',
            ],
            'Ethics' => [
                'Ethics',
            ],
            'The Contemporary World' => [
                'The Contemporary World',
            ],
            'Understanding the Self' => [
                'Understanding the Self',
            ],
        ],
        'prof_ed' => [
            'The Professional Teacher' => [
                'The Professional Teacher',
            ],
            'The Teacher and the School Curriculum' => [
                'The Teacher and the School Curriculum',
            ],
            'Foundations of Teaching-Learning Process' => [
                'Foundations of Teaching-Learning Process',
            ],
            'Methods and Strategies of Teaching' => [
                'Methods and Strategies of Teaching',
            ],
            'Educational Technology' => [
                'Educational Technology',
            ],
            'Headstart for Toddlers, Child, and Adolescent' => [
                'Headstart for Toddlers, Child, and Adolescent',
            ],
            'Validating Theoretical Knowledge in the Actual Assessment of Learning' => [
                'Validating Theoretical Knowledge in the Actual Assessment of Learning',
            ],
            'Field Study and Teaching Internship' => [
                'Field Study and Teaching Internship',
            ],
        ],
        'specialization' => [
            'English' => [
                'English literature',
                'Language and linguistics',
            ],
            'Filipino' => [
                'Wika at gramatika',
                'Panitikang panlipunan',
            ],
            'Mathematics' => [
                'Algebra and number theory',
                'Geometry and trigonometry',
            ],
            'Science' => [
                'Biology',
                'Chemistry and physics',
            ],
            'Social Studies' => [
                'Philippine history and government',
                'World history and geography',
            ],
            'MAPEH' => [
                'Music and arts',
                'Physical education and health',
            ],
            'TLE' => [
                'Home economics and entrepreneurship',
                'Industrial arts and ICT',
            ],
            'Values Education' => [
                'Foundations of values education',
                'Values integration in teaching',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Walk the catalog and create a subject, topic, lesson and quiz for each entry.
        foreach (self::CATALOG as $area => $subjects) {
            $subjectOrder = 0;

            foreach ($subjects as $subjectName => $topics) {
                $subject = Subject::updateOrCreate(
                    ['area' => $area, 'name' => $subjectName],
                    ['sort_order' => ++$subjectOrder],
                );

                $topicOrder = 0;

                foreach ($topics as $topicName) {
                    $topic = Topic::updateOrCreate(
                        ['subject_id' => $subject->id, 'name' => $topicName],
                        ['sort_order' => ++$topicOrder],
                    );

                    // Each topic gets one review lesson.
                    Lesson::updateOrCreate(
                        ['topic_id' => $topic->id, 'title' => 'Review notes: '.$topicName],
                        ['estimated_minutes' => 10 + ($topicOrder * 5)],
                    );

                    // Each topic also gets one practice quiz.
                    Quiz::updateOrCreate(
                        ['topic_id' => $topic->id, 'title' => 'Practice set: '.$topicName],
                        ['item_count' => 20, 'time_limit_minutes' => 25],
                    );
                }
            }
        }
    }
}
