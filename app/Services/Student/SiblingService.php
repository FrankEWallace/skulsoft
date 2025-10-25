<?php

namespace App\Services\Student;

use App\Models\Contact;
use App\Models\Finance\FeeConcession;
use App\Models\Student\Fee;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class SiblingService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function export(Request $request)
    {
        $parents = Contact::query()
            ->join('students', 'contacts.id', '=', 'students.contact_id')
            ->select('contacts.father_name', 'contacts.mother_name')
            ->where('students.period_id', auth()->user()->current_period_id)
            ->groupBy('contacts.father_name', 'contacts.mother_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $feeConcessions = FeeConcession::query()
            ->byPeriod()
            ->get();

        foreach ($parents as $parent) {
            $students = Student::query()
                ->select(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'batches.name as batch_name', 'courses.name as course_name', 'admissions.code_number as admission_number', 'courses.position as course_position', 'batches.position as batch_position')
                ->join('contacts', 'students.contact_id', '=', 'contacts.id')
                ->join('batches', 'students.batch_id', '=', 'batches.id')
                ->join('courses', 'batches.course_id', '=', 'courses.id')
                ->join('admissions', 'students.admission_id', '=', 'admissions.id')
                ->addSelect(['fee_concession_id' => Fee::select('fee_concession_id')
                    ->whereColumn('student_id', 'students.id')
                    ->where('fee_concession_id', '!=', null)
                    ->orderBy('id', 'desc')
                    ->limit(1),
                ])
                ->where('contacts.father_name', $parent->father_name)
                ->where('contacts.mother_name', $parent->mother_name)
                ->where('students.period_id', auth()->user()->current_period_id)->orderBy('course_position', 'asc')
                ->orderBy('batch_position', 'asc')
                ->get();

            $students->each(function ($student) use ($feeConcessions) {
                $feeConcession = $feeConcessions->firstWhere('id', $student->fee_concession_id);

                $student->fee_concession = $feeConcession?->name;
            });

            $parent->students = $students;
        }

        // $parents = $parents->sortBy(function ($parent) {
        //     return $parent->father_name;
        // });

        $parents = $parents->sortByDesc(function ($parent) {
            return $parent->students->count();
        });

        return $parents;
    }
}
