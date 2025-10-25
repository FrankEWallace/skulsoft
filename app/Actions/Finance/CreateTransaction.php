<?php

namespace App\Actions\Finance;

use App\Enums\Finance\TransactionType;
use App\Models\Finance\Ledger;
use App\Models\Finance\Transaction;
use App\Models\Finance\TransactionPayment;
use App\Models\Finance\TransactionRecord;
use App\Support\FormatCodeNumber;
use Illuminate\Support\Arr;

class CreateTransaction
{
    use FormatCodeNumber;

    public function execute(array $params = []): Transaction
    {
        $generateCodeNumber = Arr::get($params, 'generate_code_number', true);

        if (Arr::get($params, 'is_online')) {
            $generateCodeNumber = false;
        }

        if ($generateCodeNumber && Arr::get($params, 'code_number')) {
            $codeNumberDetail = [
                'number_format' => null,
                'number' => null,
                'code_number' => Arr::get($params, 'code_number'),
            ];
        } else {
            $codeNumberDetail = $generateCodeNumber ? $this->codeNumber($params) : [];
        }

        $currency = Arr::get($params, 'currency', config('config.system.currency'));

        $transaction = Transaction::forceCreate([
            'type' => Arr::get($params, 'type', TransactionType::RECEIPT->value),
            'head' => Arr::get($params, 'head'),
            'date' => Arr::get($params, 'date'),
            'amount' => Arr::get($params, 'amount', 0),
            'currency' => $currency,
            'transactionable_type' => Arr::get($params, 'transactionable_type'),
            'transactionable_id' => Arr::get($params, 'transactionable_id'),
            'remarks' => Arr::get($params, 'remarks'),
            'number_format' => Arr::get($codeNumberDetail, 'number_format'),
            'number' => Arr::get($codeNumberDetail, 'number'),
            'code_number' => Arr::get($codeNumberDetail, 'code_number'),
            'period_id' => Arr::get($params, 'period_id'),
            'payment_gateway' => Arr::get($params, 'payment_gateway'),
            'is_online' => Arr::get($params, 'is_online', false),
            'user_id' => auth()?->id() ?? Arr::get($params, 'user_id'),
            'meta' => [
                'payment_method_code' => Arr::get($params, 'payment_method_code'),
                'ledger_code' => Arr::get($params, 'ledger_code'),
                'bank_transfer_id' => Arr::get($params, 'bank_transfer_id'),
                'fee_group_name' => Arr::get($params, 'fee_group_name'),
            ],
        ]);

        $transaction->refresh();

        foreach (Arr::get($params, 'payments', []) as $payment) {
            $ledgerId = Arr::get($payment, 'ledger_id');

            TransactionPayment::forceCreate([
                'transaction_id' => $transaction->id,
                'ledger_id' => $ledgerId,
                'payment_method_id' => Arr::get($payment, 'payment_method_id'),
                'details' => Arr::get($payment, 'payment_method_details', []),
                'amount' => Arr::get($payment, 'amount', 0),
                'description' => Arr::get($payment, 'description'),
            ]);

            if ($ledgerId) {
                $ledger = Ledger::find($ledgerId);
                $ledger->updatePrimaryBalance($transaction->type, Arr::get($payment, 'amount', 0));
            }
        }

        foreach (Arr::get($params, 'records', []) as $record) {
            $ledgerId = Arr::get($record, 'ledger_id');

            TransactionRecord::forceCreate([
                'transaction_id' => $transaction->id,
                'ledger_id' => $ledgerId,
                'model_type' => Arr::get($record, 'model_type'),
                'model_id' => Arr::get($record, 'model_id'),
                'amount' => Arr::get($record, 'amount', 0),
                'remarks' => Arr::get($record, 'remarks'),
            ]);

            if ($ledgerId) {
                $ledger = Ledger::find($ledgerId);
                $ledger->updateSecondaryBalance($transaction->type, Arr::get($record, 'amount', 0));
            }
        }

        return $transaction;
    }

    private function codeNumber(array $params): array
    {
        $type = Arr::get($params, 'type', 'receipt');

        $numberPrefix = config('config.finance.'.$type.'_number_prefix');
        $numberSuffix = config('config.finance.'.$type.'_number_suffix');
        $digit = config('config.finance.'.$type.'_number_digit');

        $isOnline = Arr::get($params, 'is_online', false);

        if ($isOnline && config('config.finance.enable_online_transaction_number')) {
            $numberPrefix = config('config.finance.online_transaction_number_prefix');
            $numberSuffix = config('config.finance.online_transaction_number_suffix');
            $digit = config('config.finance.online_transaction_number_digit');
        }

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat, Arr::get($params, 'date'));

        $numberFormat = $this->preFormatForTransaction($numberFormat, [
            'payment_method' => Arr::get($params, 'payment_method_code'),
            'ledger' => Arr::get($params, 'ledger_code'),
        ]);

        if (Arr::get($params, 'batch_id')) {
            $numberFormat = $this->preFormatForAcademicBatch(Arr::get($params, 'batch_id'), $numberFormat);
        }

        if (Arr::get($params, 'course_id')) {
            $numberFormat = $this->preFormatForAcademicCourse(Arr::get($params, 'course_id'), $numberFormat);
        }

        $codeNumber = (int) Transaction::query()
            ->join('periods', 'periods.id', '=', 'transactions.period_id')
            ->where('periods.team_id', Arr::get($params, 'team_id', auth()->user()->current_team_id))
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }
}
