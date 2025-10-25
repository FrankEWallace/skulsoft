<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Payroll\PayrollStatus;
use App\Models\Config\Config;
use App\Models\Employee\Payroll\Payroll;
use App\Support\FormatCodeNumber;
use App\Support\SetConfig;
use Illuminate\Support\Arr;

class GeneratePayrollNumber
{
    use FormatCodeNumber;

    public function execute(string $batchUuid, string $teamId)
    {
        $config = Config::query()
            ->where(function ($q) use ($teamId) {
                $q->whereNull('team_id')
                    ->orWhere('team_id', $teamId);
            })
            ->whereIn('name', ['system', 'employee'])
            ->pluck('value', 'name')->all();

        (new SetConfig)->set($config);

        $codeNumberDetail = $this->codeNumber($teamId);
        $numberFormat = Arr::get($codeNumberDetail, 'number_format');
        $number = Arr::get($codeNumberDetail, 'number');
        $codeNumber = Arr::get($codeNumberDetail, 'code_number');
        $digit = Arr::get($codeNumberDetail, 'digit');

        $payrolls = Payroll::query()
            ->where('meta->batch_uuid', $batchUuid)
            ->where('meta->team_id', $teamId)
            ->where('status', PayrollStatus::PROCESSED->value)
            ->get();

        foreach ($payrolls as $payroll) {
            $codeNumber = str_replace('%NUMBER%', str_pad($number, $digit, '0', STR_PAD_LEFT), $numberFormat);

            $payroll->number_format = $numberFormat;
            $payroll->number = $number;
            $payroll->code_number = $codeNumber;
            $payroll->save();

            $number++;
        }
    }

    public function codeNumber($teamId): array
    {
        $numberPrefix = config('config.employee.payroll_number_prefix');
        $numberSuffix = config('config.employee.payroll_number_suffix');
        $digit = config('config.employee.payroll_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Payroll::query()
            ->whereHas('employee', function ($q) use ($teamId) {
                $q->byTeam($teamId);
            })
            ->whereNumberFormat($numberFormat)
            ->where('status', PayrollStatus::PROCESSED)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }
}
