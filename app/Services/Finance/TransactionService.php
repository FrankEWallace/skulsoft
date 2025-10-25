<?php

namespace App\Services\Finance;

use App\Enums\Finance\TransactionStatus;
use App\Enums\Finance\TransactionType;
use App\Enums\OptionType;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\Finance\PaymentMethodResource;
use App\Http\Resources\OptionResource;
use App\Models\Academic\Period;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Finance\TransactionPayment;
use App\Models\Finance\TransactionRecord;
use App\Models\Option;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    use FormatCodeNumber;

    private function codeNumber(string $type, array $params = []): array
    {
        $numberPrefix = config('config.finance.'.$type.'_number_prefix');
        $numberSuffix = config('config.finance.'.$type.'_number_suffix');
        $digit = config('config.finance.'.$type.'_number_digit');

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat, Arr::get($params, 'date'));

        $shortcode = [
            'payment_method' => Arr::get($params, 'payment_method'),
            'ledger' => Arr::get($params, 'ledger'),
        ];

        $numberFormat = $this->preFormatForTransaction($numberFormat, $shortcode);

        $codeNumber = (int) Transaction::query()
            ->join('periods', 'periods.id', '=', 'transactions.period_id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(): array
    {
        $types = TransactionType::getOptions();

        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::TRANSACTION_CATEGORY->value)
            ->get());

        $paymentMethods = PaymentMethodResource::collection(PaymentMethod::query()
            ->byTeam()
            ->where('is_payment_gateway', false)
            ->get());

        $onlinePaymentMethods = PaymentMethodResource::collection(PaymentMethod::query()
            ->byTeam()
            ->where('is_payment_gateway', true)
            ->get());

        $primaryLedgers = LedgerResource::collection(Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->get());

        $statuses = TransactionStatus::getOptions();

        $heads = [
            [
                'label' => trans('academic.course.props.registration_fee'),
                'value' => 'registration_fee',
            ],
            [
                'label' => trans('student.fee.fee'),
                'value' => 'student_fee',
            ],
        ];

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->get());

        return compact('types', 'categories', 'paymentMethods', 'onlinePaymentMethods', 'primaryLedgers', 'statuses', 'heads', 'periods');
    }

    public function create(Request $request): Transaction
    {
        \DB::beginTransaction();

        $transaction = Transaction::forceCreate($this->formatParams($request));

        $this->updatePayments($request, $transaction);

        $this->updateRecords($request, $transaction);

        \DB::commit();

        $transaction->addMedia($request);

        return $transaction;
    }

    private function formatParams(Request $request, ?Transaction $transaction = null): array
    {
        $primaryLedger = $request->primary_ledger;

        $formatted = [
            'type' => $request->type,
            'date' => $request->date,
            'amount' => collect($request->records)->sum('amount'),
            'category_id' => $request->category_id,
            'description' => $request->description,
            'remarks' => $request->remarks,
        ];

        if (! $transaction) {
            $codeNumberDetail = $this->codeNumber($request->type, [
                'payment_method' => $request->payment_method_code,
                'ledger' => $request->ledger_code,
                'date' => $request->date,
            ]);

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['user_id'] = auth()->id();
        }

        return $formatted;
    }

    private function updatePayments(Request $request, Transaction $transaction, string $action = 'create'): void
    {
        $paymentMethodIds = [];
        foreach ($request->payment_methods as $paymentMethod) {
            $paymentMethodIds[] = Arr::get($paymentMethod, 'payment_method_id');

            $transactionPayment = TransactionPayment::firstOrCreate([
                'transaction_id' => $transaction->id,
                'payment_method_id' => Arr::get($paymentMethod, 'payment_method_id'),
            ]);

            $transactionPayment->ledger_id = $request->primary_ledger->id;
            $transactionPayment->amount = Arr::get($paymentMethod, 'amount', 0);
            $transactionPayment->details = Arr::get($paymentMethod, 'details', []);
            $transactionPayment->save();
        }

        TransactionPayment::query()
            ->whereTransactionId($transaction->id)
            ->whereNotIn('payment_method_id', $paymentMethodIds)
            ->delete();

        $transaction->refresh();
        $transaction->load('payments.ledger');

        foreach ($transaction->payments as $payment) {
            $primaryLedger = $payment->ledger;
            $primaryLedger->updatePrimaryBalance($transaction->type, $payment->amount->value);
        }
    }

    private function updateRecords(Request $request, Transaction $transaction, string $action = 'create'): void
    {
        $ledgerIds = [];
        foreach ($request->records as $record) {
            $ledgerIds[] = Arr::get($record, 'secondary_ledger_id');

            $transactionRecord = TransactionRecord::firstOrCreate([
                'transaction_id' => $transaction->id,
                'ledger_id' => Arr::get($record, 'secondary_ledger_id'),
            ]);

            $transactionRecord->amount = Arr::get($record, 'amount');
            $transactionRecord->remarks = Arr::get($record, 'remarks');
            $transactionRecord->save();
        }

        TransactionRecord::query()
            ->whereTransactionId($transaction->id)
            ->whereNotIn('ledger_id', $ledgerIds)
            ->delete();

        $transaction->refresh();
        $transaction->load('records.ledger');

        foreach ($transaction->records as $record) {
            $secondaryLedger = $record->ledger;
            $secondaryLedger->updateSecondaryBalance($transaction->type, $record->amount->value);
        }
    }

    public function update(Transaction $transaction, Request $request): void
    {
        if ($transaction->type->value != $request->type) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_modify', ['attribute' => trans('finance.transaction.props.type')])]);
        }

        if (! $transaction->isEditable()) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        \DB::beginTransaction();

        $transaction->load('payments.ledger', 'records.ledger');

        foreach ($transaction->payments as $payment) {
            $previousLedger = $payment->ledger;
            $previousLedger->reversePrimaryBalance($transaction->type, $payment->amount->value);
        }

        foreach ($transaction->records as $record) {
            $previousLedger = $record->ledger;
            $previousLedger->reverseSecondaryBalance($transaction->type, $record->amount->value);
        }

        $transaction->forceFill($this->formatParams($request, $transaction))->save();

        $this->updatePayments($request, $transaction, 'update');

        $this->updateRecords($request, $transaction, 'udpate');

        $transaction->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Transaction $transaction): void
    {
        if (! $transaction->isEditable()) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function delete(Transaction $transaction): void
    {
        \DB::beginTransaction();

        $transaction->load('payments.ledger', 'records.ledger');

        foreach ($transaction->payments as $payment) {
            $previousLedger = $payment->ledger;
            $previousLedger->reversePrimaryBalance($transaction->type, $payment->amount->value);
        }

        foreach ($transaction->records as $record) {
            $secondaryLedger = $record->ledger;
            $secondaryLedger->reverseSecondaryBalance($transaction->type, $record->amount->value);
        }

        $transaction->delete();

        \DB::commit();
    }
}
