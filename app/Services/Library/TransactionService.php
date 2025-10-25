<?php

namespace App\Services\Library;

use App\Enums\Library\IssueTo;
use App\Enums\Library\ReturnStatus;
use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Library\Transaction;
use App\Models\Library\TransactionRecord;
use App\Models\Option;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.library.transaction_number_prefix');
        $numberSuffix = config('config.library.transaction_number_suffix');
        $digit = config('config.library.transaction_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Transaction::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request)
    {
        $to = IssueTo::getOptions();

        return compact('to');
    }

    public function actionPreRequisite(Request $request)
    {
        $conditions = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::BOOK_CONDITION)
            ->get());

        $returnStatuses = ReturnStatus::getOptions();

        return compact('conditions', 'returnStatuses');
    }

    public function create(Request $request): Transaction
    {
        \DB::beginTransaction();

        $transaction = Transaction::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $transaction);

        \DB::commit();

        return $transaction;
    }

    private function updateRecords(Request $request, Transaction $transaction): void
    {
        $bookCopyIds = [];
        foreach ($request->records as $record) {
            $bookCopyIds[] = Arr::get($record, 'copy.id');

            $transactionRecord = TransactionRecord::firstOrCreate([
                'book_transaction_id' => $transaction->id,
                'book_copy_id' => Arr::get($record, 'copy.id'),
            ]);

            $transactionRecord->uuid = Arr::get($record, 'uuid');
            $transactionRecord->save();
        }

        TransactionRecord::query()
            ->whereBookTransactionId($transaction->id)
            ->whereNotIn('book_copy_id', $bookCopyIds)
            ->delete();
    }

    private function formatParams(Request $request, ?Transaction $transaction = null): array
    {
        $formatted = [
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'transactionable_type' => $request->requester_type,
            'transactionable_id' => $request->requester_id,
            'remarks' => $request->remarks,
        ];

        if (! $transaction) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');

            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    private function validateBookReturned(Transaction $transaction): void
    {
        $hasBookReturned = $transaction->records->filter(function ($record) {
            return ! empty($record->return_date->value);
        })->count();

        if ($hasBookReturned) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function update(Request $request, Transaction $transaction): void
    {
        $this->validateBookReturned($transaction);

        \DB::beginTransaction();

        $transaction->forceFill($this->formatParams($request, $transaction))->save();

        $this->updateRecords($request, $transaction);

        \DB::commit();
    }

    public function deletable(Transaction $transaction): void
    {
        $this->validateBookReturned($transaction);
    }
}
