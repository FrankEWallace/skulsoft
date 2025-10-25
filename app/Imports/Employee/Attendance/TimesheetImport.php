<?php

namespace App\Imports\Employee\Attendance;

use App\Concerns\ItemImport;
use App\Helpers\CalHelper;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Employee;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class TimesheetImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 1000;

    protected $chunk = 1000;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('timesheet');

        $data = $this->validate($rows);

        $errors = Arr::get($data, 'errors');

        $this->checkForErrors('timesheet', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import(Arr::get($data, 'items'));
        }
    }

    private function import(array $rows)
    {
        activity()->disableLogging();

        collect($rows)->chunk($this->chunk)->each(function ($chunk) {
            Timesheet::insert($chunk->toArray());
        });

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $employees = Employee::query()
            ->select('employees.id', 'joining_date', 'leaving_date', 'code_number')
            ->byTeam()
            ->join('contacts', 'employees.contact_id', '=', 'contacts.id')
            ->get();

        $employeeCodes = $employees->pluck('code_number')->all();

        $dates = [];
        foreach ($rows as $index => $row) {
            $date = Arr::get($row, 'date');

            if (is_int($date)) {
                $date = Date::excelToDateTimeObject($date)->format('Y-m-d');
                if (CalHelper::validateDate($date)) {
                    $dates[] = $date;
                }
            }
        }

        $allDates = collect($dates);
        $minDate = $allDates->min();
        $maxDate = $allDates->max();

        $employeeWorkShifts = EmployeeWorkShift::query()
            ->select('employee_id', 'work_shift_id', 'start_date', 'end_date')
            ->whereOverlapping($minDate, $maxDate)
            ->whereIn('employee_id', $employees->pluck('id')->all())
            ->get();

        $errors = [];

        $items = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $inAtOk = false;
            $outAtOk = false;

            $employeeCode = Arr::get($row, 'employee_code');
            $date = Arr::get($row, 'date');
            $inAt = Arr::get($row, 'in_at');
            $outAt = Arr::get($row, 'out_at');

            $employee = null;
            $employeeWorkShift = null;
            if (! in_array($employeeCode, $employeeCodes)) {
                $errors[] = $this->setError($rowNo, trans('employee.employee'), 'invalid');
            } else {
                $employee = $employees->firstWhere('code_number', $employeeCode);
            }

            if (is_int($date)) {
                $date = Date::excelToDateTimeObject($date)->format('Y-m-d');
            }

            if (is_numeric($inAt)) {
                $inAt = Date::excelToDateTimeObject($inAt)->format('H:i:s');
            }

            if (is_numeric($outAt)) {
                $outAt = Date::excelToDateTimeObject($outAt)->format('H:i:s');
            }

            if (! CalHelper::validateDate($date)) {
                $errors[] = $this->setError($rowNo, trans('employee.attendance.timesheet.props.date'), 'invalid');
            } else {
                $employeeWorkShift = $employeeWorkShifts
                    ->where('employee_id', $employee?->id)
                    ->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date)
                    ->first();

                if (! $employeeWorkShift) {
                    $errors[] = [
                        'row' => $rowNo,
                        'column' => trans('employee.attendance.timesheet.props.date'),
                        'message' => trans('global.could_not_find', ['attribute' => trans('employee.attendance.work_shift.work_shift')]),
                    ];
                }
            }

            if (! CalHelper::validateDateFormat($inAt, 'H:i:s')) {
                $errors[] = $this->setError($rowNo, trans('employee.attendance.timesheet.props.in_at'), 'invalid');
            } else {
                $inAtOk = true;
            }

            if (! CalHelper::validateDateFormat($outAt, 'H:i:s')) {
                $errors[] = $this->setError($rowNo, trans('employee.attendance.timesheet.props.in_at'), 'invalid');
            } else {
                $outAtOk = true;
            }

            if ($inAtOk && $outAtOk && Carbon::parse($inAt) >= Carbon::parse($outAt)) {
                $errors[] = [
                    'row' => $rowNo,
                    'column' => trans('employee.attendance.timesheet.props.in_at'),
                    'message' => trans('employee.attendance.timesheet.start_time_should_less_than_end_time'),
                ];
            }

            $items[] = [
                'uuid' => (string) Str::uuid(),
                'employee_id' => $employee?->id,
                'work_shift_id' => $employeeWorkShift?->work_shift_id,
                'date' => $date,
                'in_at' => CalHelper::storeDateTime($date.' '.$inAt)->toDateTimeString(),
                'out_at' => CalHelper::storeDateTime($date.' '.$outAt)->toDateTimeString(),
                'is_manual' => 1,
                'meta' => json_encode(['imported' => true]),
                'created_at' => now(),
            ];
        }

        return compact('errors', 'items');
    }
}
