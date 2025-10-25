<?php

namespace App\Actions\Employee\Attendance;

use App\Contracts\PaginationHelper;
use App\Models\Employee\Employee;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchEmployeeForWorkShift extends PaginationHelper
{
    public function execute(Request $request, bool $array = false): LengthAwarePaginator|array
    {
        $request->validate([
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required',
        ]);

        $excludedEmployeeIds = [];

        if ($request->boolean('hide_employee_with_work_shift')) {
            $excludedEmployeeIds = EmployeeWorkShift::query()
                ->whereOverlapping($request->start_date, $request->end_date)
                ->pluck('employee_id')
                ->all();
        }

        $employees = Employee::query()
            ->detail()
            ->filterAccessible()
            ->whereNotIn('employees.id', $excludedEmployeeIds)
            ->where(function ($q) use ($request) {
                $q->whereNull('leaving_date')->orWhere(function ($q) use ($request) {
                    $q->whereNotNull('leaving_date')->where(function ($q) use ($request) {
                        $q->whereBetween('leaving_date', [$request->start_date, $request->end_date]);
                    });
                });
            })
            ->filter([
                'App\QueryFilters\ExactMatch:code_number',
                'App\QueryFilters\WhereInMatch:departments.uuid,department',
                'App\QueryFilters\WhereInMatch:designations.uuid,designation',
                'App\QueryFilters\WhereInMatch:branches.uuid,branch',
                'App\QueryFilters\WhereInMatch:options.uuid,employment_status',
            ])
            ->orderBy('name', 'asc')
            ->paginate($this->getPageLength(), ['*'], 'current_page');

        return $array ? $employees->toArray() : $employees;
    }
}
