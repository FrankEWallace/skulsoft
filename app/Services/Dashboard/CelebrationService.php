<?php

namespace App\Services\Dashboard;

use App\Models\Employee\Employee;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class CelebrationService
{
    public function fetch(Request $request)
    {
        $students = Student::query()
            ->byPeriod()
            ->summary()
            ->whereHas('contact', function ($q) {
                $q->whereRaw('DATE_FORMAT(birth_date, "%m-%d") = ?', [now()->format('m-d')]);
            })
            ->orderBy('birth_date', 'asc')
            ->get();

        $employees = Employee::query()
            ->summary()
            ->whereHas('contact', function ($q) {
                $q->whereRaw('DATE_FORMAT(birth_date, "%m-%d") = ?', [now()->format('m-d')]);
            })
            ->orderBy('birth_date', 'asc')
            ->get();

        $data = [];

        foreach ($students as $student) {
            $data[] = [
                'name' => $student->name,
                'birth_date' => \Cal::date($student->birth_date),
                'date' => $student->birth_date,
                'type' => 'student',
                'photo' => $student->photo_url,
                'detail' => $student->course_name.' '.$student->batch_name,
            ];
        }

        foreach ($employees as $employee) {
            $data[] = [
                'name' => $employee->name,
                'birth_date' => \Cal::date($employee->birth_date),
                'date' => $employee->birth_date,
                'type' => 'employee',
                'photo' => $employee->photo_url,
                'detail' => $employee->designation_name,
            ];
        }

        $data = collect($data)->sortByDesc('date')->values()->all();

        return $data;
    }
}
