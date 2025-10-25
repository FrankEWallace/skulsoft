<?php

namespace App\Services\Finance;

use App\Models\Finance\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentMethodService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    public function create(Request $request): PaymentMethod
    {
        \DB::beginTransaction();

        $paymentMethod = PaymentMethod::forceCreate($this->formatParams($request));

        \DB::commit();

        return $paymentMethod;
    }

    private function formatParams(Request $request, ?PaymentMethod $paymentMethod = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
            'is_payment_gateway' => $request->boolean('is_payment_gateway'),
            'payment_gateway_name' => $request->boolean('is_payment_gateway') ? $request->payment_gateway_name : null,
            'config' => [
                'code' => $request->code,
                'has_instrument_number' => $request->boolean('has_instrument_number'),
                'has_instrument_date' => $request->boolean('has_instrument_date'),
                'has_clearing_date' => $request->boolean('has_clearing_date'),
                'has_bank_detail' => $request->boolean('has_bank_detail'),
                'has_reference_number' => $request->boolean('is_payment_gateway') ? true : $request->boolean('has_reference_number'),
            ],
        ];

        if (! $paymentMethod) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, PaymentMethod $paymentMethod): void
    {
        \DB::beginTransaction();

        $paymentMethod->forceFill($this->formatParams($request, $paymentMethod))->save();

        \DB::commit();
    }

    public function deletable(PaymentMethod $paymentMethod): bool
    {
        $transactionExists = \DB::table('transaction_payments')
            ->wherePaymentMethodId($paymentMethod->id)
            ->exists();

        if ($transactionExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.payment_method.payment_method'), 'dependency' => trans('finance.transaction.transaction')])]);
        }

        return true;
    }
}
