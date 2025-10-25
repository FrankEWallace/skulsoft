<?php

namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use App\Models\Finance\Transaction;
use Illuminate\Http\Request;

class FeeMismatchController extends Controller
{
    public function __invoke(Request $request)
    {
        $recordMismatches = Transaction::query()
            ->select(
                'transactions.id',
                'transactions.cancelled_at',
                'transactions.code_number',
                'transactions.amount',
                \DB::raw('SUM(transaction_records.amount) as sum_amount')
            )
            ->leftJoin(
                'transaction_records',
                'transactions.id',
                '=',
                'transaction_records.transaction_id'
            )
            ->groupBy('transactions.id')
            ->havingRaw('SUM(transaction_records.amount) != transactions.amount')
            ->get();

        $studentFeePaymentMismatches = Transaction::query()
            ->select(
                'transactions.id',
                'transactions.cancelled_at',
                'transactions.code_number',
                'transactions.amount',
                \DB::raw("SUM(CASE WHEN default_fee_head IS NULL THEN student_fee_payments.amount ELSE 0 END) + SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head != 'additional_discount' THEN student_fee_payments.amount ELSE 0 END) - SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head = 'additional_discount' THEN student_fee_payments.amount ELSE 0 END) as sum_amount")
            )
            ->leftJoin(
                'student_fee_payments',
                'transactions.id',
                '=',
                'student_fee_payments.transaction_id'
            )
            ->groupBy('transactions.id')
            ->havingRaw("SUM(CASE WHEN default_fee_head IS NULL THEN student_fee_payments.amount ELSE 0 END) + SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head != 'additional_discount' THEN student_fee_payments.amount ELSE 0 END) - SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head = 'additional_discount' THEN student_fee_payments.amount ELSE 0 END) != transactions.amount")
            ->get();

        $transactionPaymentMismatches = Transaction::query()
            ->select(
                'transactions.id',
                'transactions.cancelled_at',
                'transactions.code_number',
                'transactions.amount',
                \DB::raw('SUM(transaction_payments.amount) as sum_amount')
            )
            ->leftJoin(
                'transaction_payments',
                'transactions.id',
                '=',
                'transaction_payments.transaction_id'
            )
            ->groupBy('transactions.id')
            ->havingRaw('SUM(transaction_payments.amount) != transactions.amount')
            ->get();

        return view('custom.fee-mismatch', compact('recordMismatches', 'studentFeePaymentMismatches', 'transactionPaymentMismatches'));
    }
}
