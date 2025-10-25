<?php

namespace App\Actions\Student;

use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GetStudentFees
{
    public function execute(Student $student): array
    {
        $students = Student::query()
            ->with('fees', 'period', 'batch.course')
            ->where('contact_id', $student->contact_id)
            ->where('id', '!=', $student->id)
            ->whereNull('cancelled_at')
            ->orderBy('start_date', 'desc')
            ->get();

        $feeRecords = [];
        $previousDues = [];
        foreach ($students as $otherStudent) {
            $feeRecords[] = [
                'uuid' => $otherStudent->uuid,
                'period' => $otherStudent->period->name,
                'batch' => $otherStudent->batch->name,
                'course' => $otherStudent->batch->course->name,
            ];

            if ($otherStudent->start_date->value < $student->start_date->value) {
                $feeSummary = $otherStudent->getFeeSummary();
                $balance = Arr::get($feeSummary, 'balance_fee');

                if ($balance->value) {
                    $previousDues[] = [
                        'uuid' => $otherStudent->uuid,
                        'period' => $otherStudent->period->name,
                        'balance' => $balance,
                    ];
                }
            }
        }

        return compact('feeRecords', 'previousDues');
    }

    public function validatePreviousDue(Student $student)
    {
        if (config('config.student.allow_flexible_installment_payment') && auth()->check() && auth()->user()->can('fee:flexible-installment-payment')) {
            return;
        }

        $previousStudent = Student::query()
            ->with('fees', 'period', 'batch.course')
            ->where('contact_id', $student->contact_id)
            ->where('id', '!=', $student->id)
            ->where('start_date', '<', $student->start_date->value)
            ->orderBy('start_date', 'desc')
            ->first();

        if (! $previousStudent) {
            return;
        }

        $feeSummary = $previousStudent->getFeeSummary();
        $balance = Arr::get($feeSummary, 'balance_fee');

        if ($balance->value) {
            throw ValidationException::withMessages(['message' => trans('student.fee.previous_due_info', ['period' => $previousStudent->period->name, 'amount' => $balance->formatted])]);
        }
    }
}
