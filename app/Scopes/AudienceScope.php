<?php

namespace App\Scopes;

use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;

trait AudienceScope
{
    public function scopeAccessible(Builder $query, ?string $date = null)
    {
        if (auth()->user()->is_default || auth()->user()->hasRole('admin')) {
            return;
        }

        if (auth()->user()->is_student_or_guardian) {
            $students = Student::query()
                ->byPeriod()
                ->record()
                ->filterForStudentAndGuardian()
                ->get();

            $query->whereHas('audiences', function ($q) use ($students) {
                $q->where(function ($q) use ($students) {
                    $q->where('audienceable_type', 'Division')
                        ->whereIn('audienceable_id', $students->pluck('division_id'));
                })->orWhere(function ($q) use ($students) {
                    $q->where('audienceable_type', 'Course')
                        ->whereIn('audienceable_id', $students->pluck('course_id'));
                })->orWhere(function ($q) use ($students) {
                    $q->where('audienceable_type', 'Batch')
                        ->whereIn('audienceable_id', $students->pluck('batch_id'));
                });
            });
        } else {
            $date ??= today()->toDateString();

            $employee = Employee::query()
                ->record(true)
                ->first();

            if (! $employee) {
                $query->whereDoesntHave('audiences');

                return;
            }

            $incharges = Incharge::query()
                ->whereIn('model_type', ['Division', 'Course', 'Batch', 'Subject'])
                ->where('employee_id', $employee->id)
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date);
                })
                ->get();

            $query->whereHas('audiences', function ($q) use ($employee, $incharges) {
                $q->where(function ($q) use ($employee) {
                    $q->where('audienceable_type', 'Department')
                        ->where('audienceable_id', $employee->department_id);
                })->orWhere(function ($q) use ($employee) {
                    $q->where('audienceable_type', 'Designation')
                        ->where('audienceable_id', $employee->designation_id);
                })->orWhere(function ($q) use ($incharges) {
                    $q->where('audienceable_type', 'Division')
                        ->whereIn('audienceable_id', $incharges->pluck('detail_id'));
                })->orWhere(function ($q) use ($incharges) {
                    $q->where('audienceable_type', 'Course')
                        ->whereIn('audienceable_id', $incharges->pluck('detail_id'));
                })->orWhere(function ($q) use ($incharges) {
                    $q->where('audienceable_type', 'Batch')
                        ->whereIn('audienceable_id', $incharges->pluck('detail_id'));
                });
            });
        }

        $query
            ->orWhere(function ($q) {
                $q->when(auth()->user()->is_student_or_guardian, function ($q) {
                    $q->orWhere('audience->student_type', '=', 'all');
                }, function ($q) {
                    $q->orWhere('audience->employee_type', '=', 'all')
                        ->orWhere('audience->student_type', '=', 'all');
                });
            });
    }
}
