<?php

namespace App\Http\Resources\Employee\Payroll;

use App\Enums\ComparisonOperator;
use App\Enums\Employee\Attendance\ProductionUnit as AttendanceProductionUnit;
use App\Enums\Employee\Payroll\PayHeadType;
use App\Enums\Employee\Payroll\SalaryStructureUnit;
use App\Enums\LogicalOperator;
use App\Http\Resources\Employee\Attendance\TypeResource as AttendanceTypeResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class SalaryTemplateRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $detailedConditionalFormulas = [];

        if ($this->type == PayHeadType::COMPUTATION && $this->getMeta('has_condition', false)) {
            foreach ($this->getMeta('conditional_formulas', []) as $conditionalFormula) {

                $conditions = [];
                foreach (Arr::get($conditionalFormula, 'conditions', []) as $condition) {

                    $logicalOperator = null;
                    if (count(Arr::get($conditionalFormula, 'conditions', [])) > 1) {
                        $logicalOperator = LogicalOperator::getDetail($condition['logical_operator']);
                    }

                    $conditions[] = [
                        ...$condition,
                        'operator' => ComparisonOperator::withOperator($condition['operator']),
                        'logical_operator' => $logicalOperator,
                    ];
                }

                $conditionalFormula['conditions'] = $conditions;

                $detailedConditionalFormulas[] = $conditionalFormula;
            }
        }

        return [
            'uuid' => $this->uuid,
            'pay_head' => PayHeadResource::make($this->whenLoaded('payHead')),
            'attendance_type' => AttendanceTypeResource::make($this->whenLoaded('attendanceType')),
            'type' => PayHeadType::getDetail($this->type),
            $this->mergeWhen($this->type != PayHeadType::PRODUCTION_BASED, [
                'unit' => SalaryStructureUnit::getDetail('monthly'),
            ]),
            $this->mergeWhen($this->type == PayHeadType::PRODUCTION_BASED, [
                'unit' => AttendanceProductionUnit::getDetail('hourly'),
            ]),
            'as_total' => $this->as_total,
            'visibility' => $this->visibility,
            'enable_user_input' => $this->enable_user_input,
            'position' => $this->position,
            'computation' => $this->computation,
            $this->mergeWhen($this->type == PayHeadType::COMPUTATION, [
                'has_range' => (bool) $this->getMeta('has_range', false),
                'min_value' => \Price::from($this->getMeta('min_value', 0)),
                'max_value' => \Price::from($this->getMeta('max_value', 0)),
                'has_condition' => (bool) $this->getMeta('has_condition', false),
                'conditional_formulas' => $this->getMeta('conditional_formulas', []),
                'detailed_conditional_formulas' => $detailedConditionalFormulas,
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
