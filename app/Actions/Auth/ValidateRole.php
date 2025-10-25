<?php

namespace App\Actions\Auth;

use App\Models\Employee\Employee;
use App\Models\Student\Student;
use App\Models\User;
use App\Support\AsStudentOrGuardian;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ValidateRole
{
    use AsStudentOrGuardian;

    public function execute(User $user)
    {
        $this->validateStudent($user);

        $this->validateGuardian($user);

        $this->validateEmployee($user);
    }

    private function validateStudent(User $user)
    {
        if (! $user->hasRole('student')) {
            return;
        }

        $students = Student::query()
            ->select('students.id', 'students.period_id', 'students.start_date', 'students.cancelled_at', 'students.admission_id', 'students.contact_id', 'admissions.leaving_date', 'contacts.user_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->where('contacts.user_id', '=', $user->id)
            ->whereNull('students.cancelled_at')
            ->get();

        if (! $students->count()) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $student = $students->count() == 1 ? $students->first() : $students->where('start_date.value', '<=', today()->toDateString())->sortByDesc('start_date.value')->first();

        if (! $student) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($student->cancelled_at->value) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($student->leaving_date && $student->leaving_date < today()->toDateString()) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('auth.login.errors.permission_disabled')]);
        }

        $preference = $user->preference;
        if (! in_array(Arr::get($preference, 'academic.period_id'), $students->pluck('period_id')->all())) {
            $preference['academic']['period_id'] = $student->period_id;
            $user->preference = $preference;
            $user->save();
        }
    }

    private function validateGuardian(User $user)
    {
        if (! $user->hasRole('guardian')) {
            return;
        }

        $studentContactIds = $this->getStudentContactIds($user);

        $students = Student::query()
            ->select('students.id', 'students.admission_id', 'students.contact_id', 'students.cancelled_at', 'admissions.leaving_date', 'contacts.user_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->whereIn('students.contact_id', $studentContactIds)
            ->whereNull('students.cancelled_at')
            ->get();

        if (! $students->count()) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $activeStudents = $students->filter(function ($student) {
            return is_null($student->leaving_date) || ($student->leaving_date && $student->leaving_date >= today()->toDateString());
        });

        if (! $activeStudents->count()) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('auth.login.errors.permission_disabled')]);
        }
    }

    private function validateEmployee(User $user)
    {
        if ($user->hasAnyRole(['admin', 'student', 'guardian', 'attendance-assistant'])) {
            return;
        }

        $employee = Employee::query()
            ->select('employees.id', 'employees.contact_id', 'employees.leaving_date', 'contacts.user_id')
            ->join('contacts', 'employees.contact_id', '=', 'contacts.id')
            ->where('joining_date', '<=', today()->toDateString())
            ->where(function ($q) {
                $q->whereNull('leaving_date')
                    ->orWhere('leaving_date', '>=', today()->toDateString());
            })
            ->where('contacts.user_id', '=', $user->id)
            ->first();

        if (! $employee) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($employee->leaving_date->value && $employee->leaving_date->value < today()->toDateString()) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('auth.login.errors.permission_disabled')]);
        }
    }
}
