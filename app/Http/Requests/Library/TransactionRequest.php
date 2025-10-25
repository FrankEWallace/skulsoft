<?php

namespace App\Http\Requests\Library;

use App\Enums\Library\IssueTo;
use App\Models\Employee\Employee;
use App\Models\Library\BookCopy;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'to' => ['required', new Enum(IssueTo::class)],
            'requester' => ['required', 'uuid'],
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.copy.uuid' => ['required', 'uuid', 'distinct'],
            'remarks' => ['nullable', 'min:2', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $transactionUuid = $this->route('transaction');

            $requester = null;
            if ($this->to == 'student') {
                $requester = Student::query()
                    ->byTeam()
                    ->whereUuid($this->requester)
                    ->getOrFail(__('student.student'), 'requester');
            } elseif ($this->to == 'employee') {
                $requester = Employee::query()
                    ->byTeam()
                    ->whereUuid($this->requester)
                    ->getOrFail(__('employee.employee'), 'requester');
            }

            $requesterType = Str::title($this->to);

            $date = $this->issue_date;

            $bookCopies = BookCopy::query()
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
                ->whereIn('book_copies.uuid', Arr::pluck($this->records, 'copy.uuid'))
                ->select('book_copies.uuid', 'book_copies.id', 'book_copies.hold_status', 'book_transactions.issue_date', 'book_transactions.uuid as transaction_uuid')
                ->get();

            foreach ($this->records as $index => $record) {
                $bookCopy = $bookCopies->firstWhere('uuid', Arr::get($record, 'copy.uuid'));

                if (! $bookCopy) {
                    throw ValidationException::withMessages(['records.'.$index.'.copy' => trans('global.could_not_find', ['attribute' => trans('library.book.book')])]);
                }

                if ($bookCopy->issue_date && $bookCopy->transaction_uuid != $transactionUuid) {
                    throw ValidationException::withMessages(['records.'.$index.'.copy' => trans('library.transaction.already_issued')]);
                }

                if (! empty($bookCopy->hold_status?->value)) {
                    throw ValidationException::withMessages(['records.'.$index.'.copy' => trans('library.transaction.on_hold')]);
                }

                $newRecords[] = [
                    'uuid' => Arr::get($record, 'uuid', (string) Str::uuid()),
                    'copy' => $bookCopy->toArray(),
                ];
            }

            $this->merge([
                'records' => $newRecords,
                'requester_type' => $requesterType,
                'requester_id' => $requester?->id,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'to' => __('library.transaction.props.to'),
            'requester' => __('library.transaction.props.requester'),
            'issue_date' => __('library.transaction.props.issue_date'),
            'due_date' => __('library.transaction.props.due_date'),
            'records' => __('library.transaction.props.details'),
            'records.*.copy.uuid' => __('library.transaction.props.number'),
            'records.*.condition' => __('library.transaction.props.condition'),
            'remarks' => __('library.transaction.props.remarks'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
