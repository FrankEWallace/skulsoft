<?php

namespace App\Jobs\Employee\Payroll;

use App\Actions\Employee\Payroll\UpdatePayrollRecord;
use App\Actions\Employee\Payroll\ValidatePayrollInput;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Models\Config\Config;
use App\Models\Employee\Payroll\Payroll;
use App\Support\SetConfig;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class PayrollProcess implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payroll;

    protected $teamId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Payroll $payroll, string $teamId)
    {
        $this->payroll = $payroll;
        $this->teamId = $teamId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // logger()->info('Processing payroll', ['payroll' => $this->payroll->id]);

        $config = Config::query()
            ->where(function ($q) {
                $q->whereNull('team_id')
                    ->orWhere('team_id', $this->teamId);
            })
            ->whereIn('name', ['system', 'employee'])
            ->pluck('value', 'name')->all();

        (new SetConfig)->set($config);

        try {
            \DB::transaction(function () {
                $this->payroll->status = PayrollStatus::PROCESSING->value;
                $this->payroll->save();

                $this->process();

                $this->payroll->status = PayrollStatus::PROCESSED->value;
                $this->payroll->setMeta([
                    'has_batch_processed' => true,
                    'processed_at' => now()->toDateTimeString(),
                ]);
                $this->payroll->save();
            });
        } catch (\Exception $e) {
            $this->payroll->status = PayrollStatus::FAILED->value;
            $this->payroll->setMeta([
                'error_message' => $e->getMessage(),
            ]);
            $this->payroll->save();
        }
    }

    protected function process()
    {
        $data = (new ValidatePayrollInput)->execute([
            'employee_id' => $this->payroll->employee_id,
            'start_date' => $this->payroll->start_date->value,
            'end_date' => $this->payroll->end_date->value,
            'team_id' => $this->teamId,
        ]);

        if (! Arr::get($data, 'status')) {
            throw new \Exception(Arr::get($data, 'code'));
        }

        $salaryStructure = Arr::get($data, 'salary_structure');
        $attendanceTypes = Arr::get($data, 'attendance_types');
        $productionAttendanceTypes = Arr::get($data, 'production_attendance_types');

        (new UpdatePayrollRecord)->execute($this->payroll, [
            'employee_id' => $this->payroll->employee_id,
            'start_date' => $this->payroll->start_date->value,
            'end_date' => $this->payroll->end_date->value,
            'salary_structure' => $salaryStructure,
            'attendance_types' => $attendanceTypes,
            'production_attendance_types' => $productionAttendanceTypes,
            'has_hourly_payroll' => Arr::get($data, 'has_hourly_payroll'),
            'ignore_attendance' => (bool) $this->payroll->getMeta('ignore_attendance'),
            'is_batch_process' => true,
        ]);
    }
}
