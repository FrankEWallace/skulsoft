<?php

namespace App\Actions\Student;

use App\Contracts\PaginationHelper;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class FetchStudentForPromotion extends PaginationHelper
{
    public function execute(array $params = [], bool $array = false)
    {
        Validator::make($params, [
            'batch' => 'required',
            'students' => 'array',
        ], [], [
            'batch' => trans('academic.batch.batch'),
            'students' => trans('student.student'),
        ])->validate();

        $selectAll = Arr::get($params, 'select_all') == true ? true : false;

        $name = Arr::get($params, 'name');
        $batch = Arr::get($params, 'batch');
        $uuids = Arr::get($params, 'students', []);

        if ($selectAll) {
            $uuids = [];
        }

        $query = Student::query()
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'contacts.first_name', 'contacts.last_name', 'contacts.contact_number', 'contacts.father_name', 'contacts.mother_name', 'contacts.email', 'contacts.birth_date', 'contacts.gender', 'admissions.code_number', 'admissions.joining_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name')
            ->byPeriod()
            ->join('contacts', function ($join) {
                $join->on('students.contact_id', '=', 'contacts.id');
            })
            ->join('admissions', function ($join) {
                $join->on('students.admission_id', '=', 'admissions.id');
            })
            ->join('batches', function ($join) {
                $join->on('students.batch_id', '=', 'batches.id')
                    ->leftJoin('courses', function ($join) {
                        $join->on('batches.course_id', '=', 'courses.id');
                    });
            })
            ->whereNull('end_date')
            ->whereNull('students.cancelled_at')
            ->where(function ($q) {
                $q->whereNull('students.meta->is_alumni')
                    ->orWhere('students.meta->is_alumni', false);
            })
            ->when($name, function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($batch, function ($q, $batch) {
                $q->where('batches.uuid', $batch);
            })
            ->when($uuids, function ($q, $uuids) {
                $q->whereIn('students.uuid', $uuids);
            });

        if ($selectAll) {
            $students = $query
                ->orderBy('name', 'asc')
                ->orderBy('number', 'asc')
                ->get();
        } else {
            $perPage = Arr::get($params, 'per_page', $this->getPageLength());

            $students = $query
                ->orderBy('name', 'asc')
                ->orderBy('number', 'asc')
                ->paginate($perPage, ['*'], 'current_page');
        }

        return $array ? $students->toArray() : $students;
    }
}
