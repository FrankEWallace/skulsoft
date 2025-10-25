<?php

namespace App\Services\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Models\Employee\Payroll\PayHead;
use App\Models\Employee\Payroll\SalaryTemplateRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PayHeadService
{
    public function preRequisite(Request $request): array
    {
        $payHeadCategories = PayHeadCategory::getOptions();

        return compact('payHeadCategories');
    }

    public function create(Request $request): PayHead
    {
        \DB::beginTransaction();

        $payHead = PayHead::forceCreate($this->formatParams($request));

        \DB::commit();

        return $payHead;
    }

    private function formatParams(Request $request, ?PayHead $payHead = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'alias' => $request->alias,
            'category' => $request->category,
            'description' => $request->description,
        ];

        if (! $payHead) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, PayHead $payHead): void
    {
        if ($request->category != $payHead->category || $request->code != $payHead->code) {
            $salaryTemplateExists = SalaryTemplateRecord::wherePayHeadId($payHead->id)->exists();

            if ($salaryTemplateExists) {
                throw ValidationException::withMessages(['message' => trans('employee.payroll.pay_head.could_not_perform_if_associated_with_salary_template')]);
            }
        }

        \DB::beginTransaction();

        $payHead->forceFill($this->formatParams($request, $payHead))->save();

        \DB::commit();
    }

    public function deletable(PayHead $payHead): void
    {
        $salaryTemplateExists = SalaryTemplateRecord::wherePayHeadId($payHead->id)->exists();

        if ($salaryTemplateExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.payroll.pay_head.pay_head'), 'dependency' => trans('employee.payroll.salary_template.salary_template')])]);
        }
    }
}
