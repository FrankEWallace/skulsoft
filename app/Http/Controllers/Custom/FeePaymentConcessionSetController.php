<?php

namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use App\Models\Student\Fee;
use App\Models\Student\FeePayment;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class FeePaymentConcessionSetController extends Controller
{
    public function __invoke(Request $request)
    {
        $skip = (int) $request->query('skip', 0);
        $limit = (int) $request->query('limit', 500);

        $students = Student::query()
            ->select('id')
            ->skip($skip)
            ->take($limit)
            ->get();

        $studentFees = Fee::query()
            ->select('id', 'student_id')
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        $feeRecords = FeeRecord::query()
            ->select('id', 'student_fee_id', 'fee_head_id', 'default_fee_head', 'amount', 'paid', 'concession')
            ->where('amount', '>', 0)
            ->where('concession', '>', 0)
            ->whereRaw('amount - concession = paid')
            ->whereIn('student_fee_id', $studentFees->pluck('id'))
            ->get();

        $feePayments = FeePayment::query()
            ->whereHas('transaction', function ($q) {
                $q->whereNull('cancelled_at')
                    ->whereNull('rejected_at')
                    ->where(function ($q) {
                        $q->where('is_online', 0)
                            ->orWhere(function ($q) {
                                $q->where('is_online', 1)
                                    ->whereNotNull('processed_at');
                            });
                    });
            })
            ->whereIn('student_fee_id', $studentFees->pluck('id'))
            ->get();

        foreach ($feeRecords as $feeRecord) {
            $studentFeePayments = $feePayments->where('student_fee_id', $feeRecord->student_fee_id);

            if ($feeRecord->fee_head_id) {
                $studentFeePayments = $studentFeePayments->where('fee_head_id', $feeRecord->fee_head_id);
            } else {
                $studentFeePayments = $studentFeePayments->where('default_fee_head', $feeRecord->default_fee_head);
            }

            $feePayment = $studentFeePayments->sortByDesc('id')->first();

            if ($feePayment && $feePayment->concession_amount->value != $feeRecord->concession->value) {
                $feePayment->concession_amount = $feeRecord->concession->value;
                $feePayment->setMeta([
                    'fixed_concession_amount' => today()->toDateString(),
                ]);
                $feePayment->save();
            }
        }
    }
}
