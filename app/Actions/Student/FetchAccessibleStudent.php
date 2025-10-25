<?php

namespace App\Actions\Student;

use App\Contracts\PaginationHelper;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class FetchAccessibleStudent extends PaginationHelper
{
    public function execute(array $params = [], bool $array = false)
    {
        Validator::make($params, [
            'students' => 'array',
        ], [], [
            'students' => trans('student.student'),
        ])->validate();

        $onDate = Arr::get($params, 'on_date') ?? today()->toDateString();

        $selectAll = Arr::get($params, 'select_all') == true ? true : false;

        $paginate = false;
        if (array_key_exists('paginate', $params) && Arr::get($params, 'paginate') == true) {
            $paginate = true;
        }

        $uuids = [];
        if (count(Arr::get($params, 'students', []))) {
            foreach (Arr::get($params, 'students', []) as $student) {
                $uuids[] = is_array($student) ? Arr::get($student, 'uuid') : $student;
            }
        }

        $name = Arr::get($params, 'name');
        $status = Arr::get($params, 'status', 'studying');
        $forSubject = (bool) Arr::get($params, 'for_subject');

        if ($selectAll) {
            $uuids = [];
        }

        $query = Student::query()
            ->when(Arr::get($params, 'show_detail'), function ($q) {
                $q->detail();
            }, function ($q) {
                $q->summary();
            })
            ->byPeriod()
            ->filterByStatus($status)
            ->filterAccessible($forSubject)
            // ->where(function ($q) use ($onDate) {
            //     $q->whereNull('admissions.leaving_date')
            //         ->orWhere('admissions.leaving_date', '>', $onDate);
            // })
            // ->where(function($q) use ($onDate) {
            //     $q->where('students.start_date', '<=', '2023-10-31');
            // })
            ->when($name, function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($uuids, function ($q, $uuids) {
                $q->whereIn('students.uuid', $uuids);
            });

        if (! $paginate) {
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
