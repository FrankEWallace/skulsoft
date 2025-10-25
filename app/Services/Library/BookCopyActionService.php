<?php

namespace App\Services\Library;

use App\Enums\Library\HoldStatus;
use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Library\BookCopy;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class BookCopyActionService
{
    public function preRequisite(Request $request, BookCopy $bookCopy)
    {
        $conditions = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::BOOK_CONDITION)
            ->get());

        return compact('conditions');
    }

    private function getBookCopyStatus(Collection $bookCopies)
    {
        $date = today()->toDateString();

        return BookCopy::query()
            ->leftJoin(\DB::raw('
            (
                SELECT book_transaction_records.*
                FROM book_transaction_records
                INNER JOIN (
                    SELECT book_copy_id, MAX(id) as latest_record_id
                    FROM book_transaction_records
                    GROUP BY book_copy_id
                ) as latest_record ON book_transaction_records.id = latest_record.latest_record_id WHERE book_transaction_records.return_date IS NULL
            ) as latest_book_transaction_records
            '), 'latest_book_transaction_records.book_copy_id', '=', 'book_copies.id')
            ->leftJoin('book_transactions', 'book_transactions.id', '=', 'latest_book_transaction_records.book_transaction_id')
            ->where(function ($query) use ($date) {
                $query->whereNull('book_transactions.issue_date')
                    ->orWhereDate('book_transactions.issue_date', '<=', $date);
            })
            ->whereHas('book', function ($query) {
                $query->byTeam();
            })
            ->whereIn('book_copies.uuid', $bookCopies->pluck('uuid'))
            ->select('book_copies.uuid', 'book_copies.id', 'book_copies.hold_status', 'book_transactions.issue_date', 'book_transactions.uuid as transaction_uuid')
            ->get();
    }

    public function updateBulkCondition(Request $request)
    {
        $request->validate([
            'book_copies' => 'array',
            'condition' => 'required|uuid',
        ]);

        $condition = Option::query()
            ->byTeam()
            ->where('type', OptionType::BOOK_CONDITION)
            ->whereUuid($request->input('condition'))
            ->first();

        if (! $condition) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $bookCopies = BookCopy::query()
            ->whereIn('uuid', $request->input('book_copies', []))
            ->get();

        $bookCopyStatuses = $this->getBookCopyStatus($bookCopies);
        $issuedBookCopies = $bookCopyStatuses->whereNotNull('issue_date')->pluck('uuid')->all();

        $updateCount = 0;
        foreach ($bookCopies as $bookCopy) {
            if (in_array($bookCopy->uuid, $issuedBookCopies)) {
                continue;
            }

            $bookCopy->condition_id = $condition->id;
            $bookCopy->save();
            $updateCount++;
        }

        return $updateCount;
    }

    public function updateBulkStatus(Request $request)
    {
        $request->validate([
            'book_copies' => 'array',
            'status' => 'required|in:hold,stock',
            'hold_status' => ['required_if:status,hold', new Enum(HoldStatus::class)],
        ]);

        $bookCopies = BookCopy::query()
            ->whereIn('uuid', $request->input('book_copies', []))
            // let them do whatever they want
            // ->when($request->status == 'stock', function ($query) use ($request) {
            //     $query->where('status', '!=', 'hold');
            // })
            ->get();

        $bookCopyStatuses = $this->getBookCopyStatus($bookCopies);
        $issuedBookCopies = $bookCopyStatuses->whereNotNull('issue_date')->pluck('uuid')->all();

        $updateCount = 0;
        foreach ($bookCopies as $bookCopy) {
            if (in_array($bookCopy->uuid, $issuedBookCopies)) {
                continue;
            }

            if ($request->status == 'hold') {
                $bookCopy->hold_status = $request->hold_status;
            } else {
                $bookCopy->hold_status = null;
            }
            $bookCopy->save();
            $updateCount++;
        }

        return $updateCount;
    }

    public function updateBulkLocation(Request $request)
    {
        $request->validate([
            'book_copies' => 'array',
            'room_number' => ['nullable', 'integer', 'min:1', 'max:100'],
            'rack_number' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'shelf_number' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $bookCopies = BookCopy::query()
            ->whereIn('uuid', $request->input('book_copies', []))
            ->get();

        $bookCopyStatuses = $this->getBookCopyStatus($bookCopies);
        $issuedBookCopies = $bookCopyStatuses->whereNotNull('issue_date')->pluck('uuid')->all();

        $updateCount = 0;
        foreach ($bookCopies as $bookCopy) {
            if (in_array($bookCopy->uuid, $issuedBookCopies)) {
                continue;
            }

            $bookCopy->room_number = $request->room_number;
            $bookCopy->rack_number = $request->rack_number;
            $bookCopy->shelf_number = $request->shelf_number;
            $bookCopy->save();
            $updateCount++;
        }

        return $updateCount;
    }
}
