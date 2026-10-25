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
            'English' => [
                'Grammar and usage',
                'Reading comprehension',
                'Speech and oral communication',
                'Philippine and world literature',
            ],
            'Filipino' => [
                'Komunikasyon sa akademikong Filipino',
                'Masining na pagpapahayag',
                'Panitikang Filipino',
            ],
            'Mathematics' => [
                'Fundamentals of mathematics',
                'Plane geometry',
                'Statistics and probability',
            ],
            'Science' => [
                'General science',
                'Biological science',
                'Physical science',
                'Earth science',
            ],
            'Social Studies' => [
                'Philippine history',
                'Government and the Constitution',
                'Economics',
                'Society and culture',
            ],
            'Information and Communications Technology' => [
                'Computer concepts and productivity tools',
                'Internet literacy and digital citizenship',
            ],
        ],
        'prof_ed' => [
            'The Teaching Profession' => [
                'Philosophical foundations of education',
                'The Code of Ethics for Professional Teachers',
                'Legal foundations of education',
            ],
            'Child and Adolescent Learners and Learning Principles' => [
                'Theories of development',
                'Learner differences',
                'Growth and development across the life span',
            ],
            'Facilitating Learner-Centered Teaching' => [
                'Learning theories',
                'Motivation and classroom management',
                'Learner-centered strategies',
            ],
            'The Teacher and the School Curriculum' => [
                'Curriculum design',
                'Curriculum implementation and evaluation',
                'The Philippine basic education curriculum',
            ],
            'Assessment in Learning' => [
                'Constructing assessment tools',
                'Item analysis and grading',
                'Assessment for learning',
            ],
            'Technology for Teaching and Learning' => [
                'Digital tools for instruction',
                'Instructional media design',
                'Digital citizenship',
            ],
            'The Teacher and the Community' => [
                'School culture and leadership',
                'Community partnerships',
            ],
            'Research and Reflective Practice' => [
                'Action research',
                'Reflective teaching',
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

                    Lesson::updateOrCreate(
                        ['topic_id' => $topic->id, 'title' => 'Review notes: '.$topicName],
                        ['estimated_minutes' => 10 + ($topicOrder * 5)],
                    );

                    Quiz::updateOrCreate(
                        ['topic_id' => $topic->id, 'title' => 'Practice set: '.$topicName],
                        ['item_count' => 20, 'time_limit_minutes' => 25],
                    );
                }
            }
        }
    }
}
