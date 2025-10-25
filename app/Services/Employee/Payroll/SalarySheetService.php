<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\FetchAllEmployee;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Helpers\CalHelper;
use App\Helpers\CurrencyConverter;
use App\Models\Account;
use App\Models\Employee\Payroll\PayHead;
use App\Models\Employee\Payroll\Payroll;
use App\Models\Employee\Payroll\SalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SalarySheetService
{
    public function generate(Request $request)
    {
        $startDate = CalHelper::validateDate($request->query('salaryStartDate')) ? $request->query('salaryStartDate') : null;
        $endDate = CalHelper::validateDate($request->query('salaryEndDate')) ? $request->query('salaryEndDate') : null;

        if (! $startDate || ! $endDate) {
            abort(404);
        }

        if ($startDate > $endDate) {
            abort(404);
        }

        $request->merge(['paginate' => false]);

        $inclusions = Str::toArray($request->query('inclusions'));

        $employees = (new FetchAllEmployee)->execute($request);

        $payrolls = Payroll::query()
            ->with(['records', 'records.payHead'])
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->whereIn('employee_id', $employees->pluck('id')->all())
            ->where('status', PayrollStatus::PROCESSED->value)
            ->get();

        $salaryStructureIds = $payrolls->pluck('salary_structure_id')->unique()->all();
        $salaryTemplateIds = SalaryStructure::query()
            ->whereIn('id', $salaryStructureIds)
            ->pluck('salary_template_id')
            ->unique()
            ->all();

        $asTotalPayHeads = [];
        foreach ($payrolls as $payroll) {
            foreach ($payroll->records as $record) {
                if ($record->getMeta('as_total')) {
                    $asTotalPayHeads[] = $record->payHead->code;
                }
            }
        }

        $asTotalPayHeads = array_unique($asTotalPayHeads);

        $forceHideAsTotalComponent = false;
        if (! config('config.employee.show_payroll_as_total_component') && count($salaryTemplateIds) == 1) {
            $forceHideAsTotalComponent = true;
        }

        $accounts = Account::query()
            ->where('accountable_type', 'Contact')
            ->whereIn('accountable_id', $employees->pluck('contact_id')->all())
            ->get();

        $rows = [];

        $row = [];

        array_push($row, [
            'key' => 'sno',
            'type' => 'header',
            'rowspan' => 2,
            'label' => '#',
        ]);

        array_push($row, [
            'key' => 'name',
            'type' => 'header',
            'rowspan' => 2,
            'label' => 'Name',
        ]);

        if (in_array('code_number', $inclusions)) {
            array_push($row, [
                'key' => 'code_number',
                'type' => 'header',
                'rowspan' => 2,
                'label' => 'Code Number',
            ]);
        }

        if (in_array('designation', $inclusions)) {
            array_push($row, [
                'key' => 'designation',
                'type' => 'header',
                'rowspan' => 2,
                'label' => 'Designation',
            ]);
        }

        if (in_array('attendance', $inclusions)) {
            array_push($row, [
                'key' => 'attendance',
                'type' => 'header',
                'rowspan' => 2,
                'label' => 'Attendance',
            ]);
        }

        $payHeads = PayHead::query()
            ->byTeam()
            ->orderBy('position', 'asc')
            ->get();

        if (in_array('earning_component', $inclusions)) {
            if ($forceHideAsTotalComponent) {
                $payHeadCount = $payHeads
                    ->filter(function ($payHead) use ($asTotalPayHeads) {
                        return ! in_array($payHead->code, $asTotalPayHeads);
                    })
                    ->where('category.value', 'earning')
                    ->count();
            } else {
                $payHeadCount = $payHeads->where('category.value', 'earning')->count();
            }

            array_push($row, [
                'key' => 'earning',
                'type' => 'header',
                'colspan' => $payHeadCount + 1,
                'label' => trans('employee.payroll.pay_head.categories.earning'),
            ]);
        }

        if (in_array('deduction_component', $inclusions)) {
            if ($forceHideAsTotalComponent) {
                $payHeadCount = $payHeads
                    ->filter(function ($payHead) use ($asTotalPayHeads) {
                        return ! in_array($payHead->code, $asTotalPayHeads);
                    })
                    ->where('category.value', 'deduction')
                    ->count() + $payHeads->filter(function ($payHead) {
                        return $payHead->category->value == 'employee_contribution';
                    })->count();
            } else {
                $payHeadCount = $payHeads->where('category.value', 'earning')->count() + $payHeads->where('category.value', 'employee_contribution')->count();
            }

            array_push($row, [
                'key' => 'deduction',
                'type' => 'header',
                'colspan' => $payHeadCount + 1,
                'label' => trans('employee.payroll.pay_head.categories.deduction'),
            ]);
        }

        if (in_array('net_salary', $inclusions)) {
            array_push($row, [
                'key' => 'net_salary',
                'type' => 'header',
                'rowspan' => 2,
                'label' => trans('employee.payroll.salary_structure.props.net_salary'),
            ]);
        }

        if (in_array('employer_contribution', $inclusions)) {
            if ($forceHideAsTotalComponent) {
                $payHeadCount = $payHeads
                    ->filter(function ($payHead) use ($asTotalPayHeads) {
                        return ! in_array($payHead->code, $asTotalPayHeads);
                    })
                    ->where('category.value', 'employer_contribution')
                    ->count();
            } else {
                $payHeadCount = $payHeads->where('category.value', 'employer_contribution')->count();
            }

            array_push($row, [
                'key' => 'employer_contribution',
                'type' => 'header',
                'colspan' => $payHeadCount + 1,
                'label' => trans('employee.payroll.pay_head.categories.employer_contribution'),
            ]);
        }

        if (in_array('total_expense', $inclusions)) {
            array_push($row, [
                'key' => 'total_expense',
                'type' => 'header',
                'rowspan' => 2,
                'label' => trans('employee.payroll.salary_structure.props.total_expense'),
            ]);
        }

        if (in_array('signature', $inclusions)) {
            array_push($row, [
                'key' => 'signature',
                'type' => 'header',
                'rowspan' => 2,
                'label' => trans('employee.signature'),
            ]);
        }

        $rows[] = $row;

        $row = [];

        $headTotal = [];
        $grandTotalEarning = 0;
        $grandTotalDeduction = 0;
        $grandTotalEmployerContribution = 0;
        $grandTotalExpense = 0;

        foreach ($payHeads as $payHead) {
            $headTotal[$payHead->code] = 0;
        }

        if (in_array('earning_component', $inclusions)) {
            foreach ($payHeads->where('category.value', 'earning') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'earning_'.$payHead->code,
                    'type' => 'header',
                    'label' => $payHead->code,
                    'label_detail' => $payHead->name,
                ]);
            }

            array_push($row, [
                'key' => 'total_earning',
                'type' => 'header',
                'label' => trans('employee.payroll.props.total'),
            ]);
        }

        if (in_array('deduction_component', $inclusions)) {
            foreach ($payHeads->where('category.value', 'deduction') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'deduction_'.$payHead->code,
                    'type' => 'header',
                    'label' => $payHead->code,
                    'label_detail' => $payHead->name,
                ]);
            }

            foreach ($payHeads->where('category.value', 'employee_contribution') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'employee_contribution_'.$payHead->code,
                    'type' => 'header',
                    'label' => $payHead->code,
                    'label_detail' => $payHead->name,
                ]);
            }

            array_push($row, [
                'key' => 'total_deduction',
                'type' => 'header',
                'label' => trans('employee.payroll.props.total'),
            ]);
        }

        if (in_array('employer_contribution', $inclusions)) {
            foreach ($payHeads->where('category.value', 'employer_contribution') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'employer_contribution_'.$payHead->code,
                    'type' => 'header',
                    'label' => $payHead->code,
                    'label_detail' => $payHead->name,
                ]);
            }

            array_push($row, [
                'key' => 'total_employer_contribution',
                'type' => 'header',
                'label' => trans('employee.payroll.props.total'),
            ]);
        }

        $rows[] = $row;

        $summary = [];
        foreach ($payrolls as $index => $payroll) {
            $row = [];
            $employee = $employees->firstWhere('id', $payroll->employee_id);

            array_push($row, [
                'key' => 'sno',
                'type' => 'data',
                'label' => $index + 1,
            ]);

            array_push($row, [
                'key' => 'name',
                'type' => 'data',
                'label' => $employee->name,
            ]);

            if (in_array('code_number', $inclusions)) {
                array_push($row, [
                    'key' => 'code_number',
                    'type' => 'data',
                    'label' => $employee->code_number,
                ]);
            }

            if (in_array('designation', $inclusions)) {
                array_push($row, [
                    'key' => 'designation',
                    'type' => 'data',
                    'label' => $employee->designation_name,
                ]);
            }

            if (in_array('attendance', $inclusions)) {
                array_push($row, [
                    'key' => 'attendance',
                    'type' => 'data',
                    'label' => $payroll->getMeta('working_days', 0),
                ]);
            }

            $totalEarning = 0;
            foreach ($payHeads->where('category.value', 'earning') as $payHead) {
                $record = $payroll->records->firstWhere('pay_head_id', $payHead->id);

                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                if (in_array('earning_component', $inclusions)) {
                    array_push($row, [
                        'key' => 'earning_'.$payHead->code,
                        'type' => 'data',
                        'data_type' => 'numeric',
                        'label' => $record?->visibility ? $record?->amount?->formatted : '-',
                    ]);
                }

                if (! $record->getMeta('as_total')) {
                    $headTotal[$payHead->code] += $record?->amount?->value ?? 0;
                    $totalEarning += $record?->amount?->value ?? 0;
                }
            }

            if (in_array('earning_component', $inclusions)) {
                array_push($row, [
                    'key' => 'total_earning',
                    'type' => 'data',
                    'data_type' => 'numeric',
                    'label' => \Price::from($totalEarning)->formatted,
                ]);
            }

            $totalDeduction = 0;
            foreach ($payHeads->where('category.value', 'deduction') as $payHead) {
                $record = $payroll->records->firstWhere('pay_head_id', $payHead->id);

                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                if (in_array('deduction_component', $inclusions)) {
                    array_push($row, [
                        'key' => 'deduction_'.$payHead->code,
                        'type' => 'data',
                        'data_type' => 'numeric',
                        'label' => $record?->visibility ? $record?->amount?->formatted : '-',
                    ]);
                }

                if (! $record->getMeta('as_total')) {
                    $headTotal[$payHead->code] += $record?->amount?->value ?? 0;
                    $totalDeduction += $record?->amount?->value ?? 0;
                }
            }

            foreach ($payHeads->where('category.value', 'employee_contribution') as $payHead) {
                $record = $payroll->records->firstWhere('pay_head_id', $payHead->id);

                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                if (in_array('deduction_component', $inclusions)) {
                    array_push($row, [
                        'key' => 'employee_contribution_'.$payHead->code,
                        'type' => 'data',
                        'data_type' => 'numeric',
                        'label' => $record?->visibility ? $record?->amount?->formatted : '-',
                    ]);
                }

                if (! $record->getMeta('as_total')) {
                    $headTotal[$payHead->code] += $record?->amount?->value ?? 0;
                    $totalDeduction += $record?->amount?->value ?? 0;
                }
            }

            if (in_array('deduction_component', $inclusions)) {
                array_push($row, [
                    'key' => 'total_deduction',
                    'type' => 'data',
                    'data_type' => 'numeric',
                    'label' => \Price::from($totalDeduction)->formatted,
                ]);
            }

            $netEarning = $totalEarning - $totalDeduction;

            if (in_array('net_salary', $inclusions)) {
                array_push($row, [
                    'key' => 'net_salary',
                    'type' => 'data',
                    'data_type' => 'numeric',
                    'label' => \Price::from($netEarning)->formatted,
                ]);
            }

            $totalEmployerContribution = 0;
            foreach ($payHeads->where('category.value', 'employer_contribution') as $payHead) {
                $record = $payroll->records->firstWhere('pay_head_id', $payHead->id);

                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                if (in_array('employer_contribution', $inclusions)) {
                    array_push($row, [
                        'key' => 'employer_contribution_'.$payHead->code,
                        'type' => 'data',
                        'data_type' => 'numeric',
                        'label' => $record?->visibility ? $record?->amount?->formatted : '-',
                    ]);
                }

                if (! $record->getMeta('as_total')) {
                    $headTotal[$payHead->code] += $record?->amount?->value ?? 0;
                    $totalEmployerContribution += $record?->amount?->value;
                }
            }

            if (in_array('employer_contribution', $inclusions)) {
                array_push($row, [
                    'key' => 'total_employer_contribution',
                    'type' => 'data',
                    'data_type' => 'numeric',
                    'label' => \Price::from($totalEmployerContribution)->formatted,
                ]);
            }

            $totalExpense = $netEarning + $totalEmployerContribution;

            if (in_array('total_expense', $inclusions)) {
                array_push($row, [
                    'key' => 'total_expense',
                    'type' => 'data',
                    'data_type' => 'numeric',
                    'label' => \Price::from($totalExpense)->formatted,
                ]);
            }

            if (in_array('signature', $inclusions)) {
                array_push($row, [
                    'key' => 'signature',
                    'type' => 'data',
                    'label' => '',
                ]);
            }

            $grandTotalEarning += $totalEarning;
            $grandTotalDeduction += $totalDeduction;
            $grandTotalEmployerContribution += $totalEmployerContribution;
            $grandTotalExpense += $totalExpense;

            $rows[] = $row;

            $account = $accounts->firstWhere('accountable_id', $employee->contact_id);
            $summary[] = [
                'name' => $employee->name,
                'code_number' => $employee->code_number,
                'account_number' => $account?->number,
                'account_name' => $account?->name,
                'bank_name' => Arr::get($account?->bank_details ?? [], 'bank_name'),
                'branch_name' => Arr::get($account?->bank_details ?? [], 'branch_name'),
                'bank_code1' => Arr::get($account?->bank_details ?? [], 'bank_code1'),
                'bank_code2' => Arr::get($account?->bank_details ?? [], 'bank_code2'),
                'bank_code3' => Arr::get($account?->bank_details ?? [], 'bank_code3'),
                'net_salary' => \Price::from($netEarning)->formatted,
                'total_expense' => \Price::from($totalExpense)->formatted,
            ];
        }

        $row = [];
        array_push($row, [
            'key' => 'total',
            'type' => 'footer',
            'label' => '',
        ]);

        $colspan = 1;
        if (in_array('attendance', $inclusions)) {
            $colspan++;
        }

        if (in_array('code_number', $inclusions)) {
            $colspan++;
        }

        if (in_array('designation', $inclusions)) {
            $colspan++;
        }

        array_push($row, [
            'key' => 'total',
            'type' => 'footer',
            'colspan' => $colspan,
            'label' => trans('employee.payroll.props.total'),
        ]);

        if (in_array('earning_component', $inclusions)) {
            foreach ($payHeads->where('category.value', 'earning') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'total_earning_'.$payHead->code,
                    'type' => 'footer',
                    'data_type' => 'numeric',
                    'label' => \Price::from($headTotal[$payHead->code])->formatted,
                ]);
            }

            array_push($row, [
                'key' => 'total_earning',
                'type' => 'footer',
                'data_type' => 'numeric',
                'label' => \Price::from($grandTotalEarning)->formatted,
            ]);
        }

        if (in_array('deduction_component', $inclusions)) {
            foreach ($payHeads->where('category.value', 'deduction') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'total_deduction_'.$payHead->code,
                    'type' => 'footer',
                    'data_type' => 'numeric',
                    'label' => \Price::from($headTotal[$payHead->code])->formatted,
                ]);
            }

            foreach ($payHeads->where('category.value', 'employee_contribution') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'total_deduction_'.$payHead->code,
                    'type' => 'footer',
                    'data_type' => 'numeric',
                    'label' => \Price::from($headTotal[$payHead->code])->formatted,
                ]);
            }

            array_push($row, [
                'key' => 'total_deduction',
                'type' => 'footer',
                'data_type' => 'numeric',
                'label' => \Price::from($grandTotalDeduction)->formatted,
            ]);
        }

        if (in_array('net_salary', $inclusions)) {
            array_push($row, [
                'key' => 'total_net_salary',
                'type' => 'footer',
                'data_type' => 'numeric',
                'label' => \Price::from($grandTotalEarning - $grandTotalDeduction)->formatted,
            ]);
        }

        if (in_array('employer_contribution', $inclusions)) {
            foreach ($payHeads->where('category.value', 'employer_contribution') as $payHead) {
                if ($forceHideAsTotalComponent && in_array($payHead->code, $asTotalPayHeads)) {
                    continue;
                }

                array_push($row, [
                    'key' => 'total_employer_contribution_'.$payHead->code,
                    'type' => 'footer',
                    'data_type' => 'numeric',
                    'label' => \Price::from($headTotal[$payHead->code])->formatted,
                ]);
            }

            array_push($row, [
                'key' => 'total_employer_contribution',
                'type' => 'footer',
                'data_type' => 'numeric',
                'label' => \Price::from($grandTotalEmployerContribution)->formatted,
            ]);
        }

        if (in_array('total_expense', $inclusions)) {
            array_push($row, [
                'key' => 'total_expense',
                'type' => 'footer',
                'data_type' => 'numeric',
                'label' => \Price::from($grandTotalExpense)->formatted,
            ]);
        }

        if (in_array('signature', $inclusions)) {
            array_push($row, [
                'key' => 'signature',
                'type' => 'footer',
                'label' => '',
            ]);
        }

        $rows[] = $row;

        $summaryTotal = [
            'grand_net_salary_in_words' => CurrencyConverter::toWord($grandTotalEarning - $grandTotalDeduction),
            'grand_net_salary' => \Price::from($grandTotalEarning - $grandTotalDeduction)->formatted,
            'grand_total_expense_in_words' => CurrencyConverter::toWord($grandTotalExpense),
            'grand_total_expense' => \Price::from($grandTotalExpense)->formatted,
        ];

        $params = [
            'start_date' => \Cal::from($startDate),
            'end_date' => \Cal::from($endDate),
        ];

        return view()->first([
            'print.custom.employee.payroll.salary-sheet',
            'print.employee.payroll.salary-sheet',
        ], compact('payrolls', 'rows', 'params', 'summary', 'summaryTotal'));
    }
}
