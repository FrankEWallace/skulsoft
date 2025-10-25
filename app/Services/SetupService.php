<?php

namespace App\Services;

use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use App\Models\Academic\Division;
use App\Models\Academic\Period;
use App\Models\Academic\Program;
use App\Models\Academic\Subject;
use App\Models\Student\Student;

class SetupService
{
    public function handle()
    {
        $program = Program::query()
            ->byTeam()
            ->count();

        $period = Period::query()
            ->byTeam()
            ->count();

        $division = Division::query()
            ->byPeriod()
            ->count();

        $course = Course::query()
            ->byPeriod()
            ->count();

        $batch = Batch::query()
            ->byPeriod()
            ->count();

        $subject = Subject::query()
            ->byPeriod()
            ->count();

        $student = Student::query()
            ->byTeam()
            ->count();

        $header = 'This wizard will assist you in setting up your institute. You can skip any step and come back later to complete it.';

        $footer = 'You can always come back to this wizard to complete the steps.';

        $completed = 'Hurray! You have completed all the steps. You can now start using the application.';

        $steps = [
            [
                'name' => 'academic.program',
                'is_completed' => $program > 0 ? true : false,
                'title' => trans('global.add', ['attribute' => trans('academic.program.program')]),
                'summary' => '',
                'description' => 'Programs are the main part of the academic structure. A program is a collection of courses that leads to an academic degree or certificate. For example, K12 is a program at school level. BTech is a program at college level. A program can have multiple batches, courses, subjects etc.',
                'route' => 'AcademicProgramCreate',
                'icon' => 'fas fa-graduation-cap',
            ],
            [
                'name' => 'academic.period',
                'is_completed' => $period > 0 ? true : false,
                'title' => trans('global.add', ['attribute' => trans('academic.period.period')]),
                'summary' => '',
                'description' => 'Periods are the academic years. For example, 2019-2020 is a period. A period can have multiple divisions, courses, subjects etc. Only one period can be active at a time. The active period is used to filter the data in the application.',
                'route' => 'AcademicPeriodCreate',
                'icon' => 'fas fa-graduation-cap',
            ],
            [
                'name' => 'academic.division',
                'is_completed' => $division > 0 ? true : false,
                'title' => trans('global.add', ['attribute' => trans('academic.division.division')]),
                'summary' => '',
                'description' => 'Divisions are group of similar courses. For example, LKG, UKG can be grouped under Pre-Primary division. A division can have multiple courses, batches, subjects etc.',
                'route' => 'AcademicDivisionCreate',
                'icon' => 'fas fa-graduation-cap',
            ],
            [
                'name' => 'academic.course',
                'is_completed' => $course > 0 ? true : false,
                'title' => trans('global.add', ['attribute' => trans('academic.course.course')]),
                'summary' => '',
                'description' => 'Courses are the basic building block of the academic structure. A course is a collection of subjects that leads to an academic degree or certificate. A course can have multiple batches, subjects etc.',
                'route' => 'AcademicCourseCreate',
                'icon' => 'fas fa-graduation-cap',
            ],
            [
                'name' => 'academic.batch',
                'is_completed' => $batch > 0 ? true : false,
                'title' => trans('global.add', ['attribute' => trans('academic.batch.batch')]),
                'summary' => '',
                'description' => 'Batches are the group of students under a course. For example, 2019 IT batch can be created under BTech course. A batch can have multiple subjects etc.',
                'route' => 'AcademicBatchCreate',
                'icon' => 'fas fa-graduation-cap',
            ],
            [
                'name' => 'academic.subject',
                'is_completed' => $subject > 0 ? true : false,
                'title' => trans('global.add', ['attribute' => trans('academic.subject.subject')]),
                'summary' => '',
                'description' => 'Subjects are the essential part of the academic structure. A subject is a collection of topics that leads to an academic degree or certificate. A subject can have multiple topics etc.',
                'route' => 'AcademicSubjectCreate',
                'icon' => 'fas fa-graduation-cap',
            ],
            [
                'name' => 'student',
                'is_completed' => $student > 0 ? true : false,
                'title' => trans('global.add', ['attribute' => trans('student.student')]),
                'summary' => '',
                'description' => 'Register your first student. Start with registration, fill out the student details, add the student to a batch and you are good to go.',
                'route' => 'StudentRegistrationCreate',
                'icon' => 'fas fa-graduation-cap',
            ],
        ];

        return compact('steps', 'header', 'footer', 'completed');
    }
}
