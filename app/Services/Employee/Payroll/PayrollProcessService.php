<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\FetchAllEmployee;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Jobs\Employee\Payroll\PayrollBatchProcess;
use App\Jobs\Employee\Payroll\PayrollProcess;
use App\Models\Employee\Payroll\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollProcessService
{
    public function process(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== PayrollStatus::INITIATED) {
            throw ValidationException::withMessages([
                'payroll' => trans('general.errors.invalid_input'),
            ]);
        }

        PayrollProcess::dispatchSync($payroll, $payroll->getMeta('team_id'));
    }

    public function bulkProcess(Request $request)
    {
        $request->merge([
            'paginate' => false,
            'status' => 'active',
            'date' => $request->start_date,
        ]);

        $employees = (new FetchAllEmployee)->execute($request);

        $batchUuid = (string) Str::uuid();
        $teamId = auth()->user()->current_team_id;

        $employees->filter(function ($employee) use ($request) {
            return empty($employee->leaving_date) || $employee->leaving_date >= $request->start_date;
        })->each(function ($employee) use ($request, $batchUuid, $teamId) {
            Payroll::forceCreate([
                'employee_id' => $employee->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => PayrollStatus::INITIATED->value,
                'meta' => [
                    'team_id' => $teamId,
                    'batch_uuid' => $batchUuid,
                    'ignore_attendance' => $request->boolean('ignore_attendance'),
                ],
            ]);
        });

        PayrollBatchProcess::dispatch($batchUuid, $teamId);

        return $batchUuid;
    }
}
