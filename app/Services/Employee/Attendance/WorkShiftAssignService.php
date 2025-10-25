<?php

namespace App\Services\Employee\Attendance;

use App\Actions\Employee\Attendance\FetchEmployeeForWorkShift;
use App\Http\Resources\Employee\Attendance\EmployeeWorkShiftResource;
use App\Http\Resources\Employee\Attendance\WorkShiftResource;
use App\Models\Employee\Attendance\WorkShift;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WorkShiftAssignService
{
    public function preRequisite(Request $request): array
    {
        $workShifts = WorkShiftResource::collection(WorkShift::byTeam()->get());

        return compact('workShifts');
    }

    public function fetch(Request $request)
    {
        $employees = (new FetchEmployeeForWorkShift)->execute($request);
        $employeeIds = $employees->pluck('id')->all();

        $workShifts = EmployeeWorkShift::query()
            ->select('work_shifts.name', 'work_shifts.code', 'employee_work_shifts.start_date', 'employee_work_shifts.end_date', 'employee_work_shifts.employee_id')
            ->join('work_shifts', function ($join) {
                $join->on('employee_work_shifts.work_shift_id', '=', 'work_shifts.id');
            })
            ->whereIn('employee_id', $employeeIds)
            ->whereOverlapping($request->start_date, $request->end_date)
            ->orderBy('start_date', 'asc')
            ->get();

        foreach ($employees as $employee) {
            $employeeWorkShifts = $workShifts->where('employee_id', $employee->id)->toArray();
            $employee->work_shifts = Arr::map($employeeWorkShifts, function ($workShift) {
                return [
                    'name' => Arr::get($workShift, 'name'),
                    'code' => Arr::get($workShift, 'code'),
                    'start_date' => \Cal::date(Arr::get($workShift, 'start_date')),
                    'end_date' => \Cal::date(Arr::get($workShift, 'end_date')),
                ];
            });
        }

        return EmployeeWorkShiftResource::collection($employees);
    }

    public function assign(Request $request)
    {
        $request->merge(['hide_employee_with_work_shift' => true]);

        $employees = (new FetchEmployeeForWorkShift)->execute($request);

        foreach ($request->employees as $input) {
            $employee = Arr::first($employees, function ($item) use ($input) {
                return Arr::get($item, 'uuid') == Arr::get($input, 'uuid');
            });

            if ($employee && Arr::get($input, 'work_shift_id')) {
                EmployeeWorkShift::forceCreate([
                    'employee_id' => $employee->id,
                    'work_shift_id' => Arr::get($input, 'work_shift_id'),
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'remarks' => Arr::get($input, 'remarks'),
                ]);
            }
        }
    }
}
