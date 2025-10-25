<?php

namespace App\Http\Requests\Library;

use App\Enums\OptionType;
use App\Models\Library\Book;
use App\Models\Library\BookCopy;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookAdditionRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'copies' => ['required', 'array', 'min:1'],
            'copies.*.book' => ['required', 'array'],
            'copies.*.book.uuid' => ['required', 'uuid'],
            'copies.*.number' => ['required', 'string', 'min:1', 'max:30', 'distinct'],
            'copies.*.condition' => ['required', 'uuid'],
            'copies.*.vendor' => ['nullable', 'string', 'min:1', 'max:100'],
            'copies.*.invoice_number' => ['nullable', 'string', 'min:1', 'max:100'],
            'copies.*.invoice_date' => ['nullable', 'date_format:Y-m-d'],
            'copies.*.room_number' => ['nullable', 'integer', 'min:1', 'max:100'],
            'copies.*.rack_number' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'copies.*.shelf_number' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'remarks' => ['nullable', 'min:2', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $bookAdditionUuid = $this->route('book_addition');

            $conditions = Option::query()
                ->byTeam()
                ->whereType(OptionType::BOOK_CONDITION->value)
                ->get();

            $existingBookNumbers = BookCopy::query()
                ->when($bookAdditionUuid, function ($q) use ($bookAdditionUuid) {
                    $q->whereHas('addition', function ($q) use ($bookAdditionUuid) {
                        $q->where('uuid', '!=', $bookAdditionUuid);
                    });
                })
                ->whereHas('book', function ($q) {
                    $q->byTeam();
                })
                ->get()
                ->pluck('number')
                ->all();

            $bookUuids = Arr::pluck($this->copies, 'book.uuid');

            $existingBooks = Book::query()
                ->byTeam()
                ->whereIn('uuid', $bookUuids)
                ->get();

            $newCopies = [];
            foreach ($this->copies as $index => $copy) {

                if (in_array(Arr::get($copy, 'number'), $existingBookNumbers)) {
                    throw ValidationException::withMessages(['copies.'.$index.'.number' => trans('library.book_addition.number_already_exists')]);
                }

                $condition = $conditions->firstWhere('uuid', Arr::get($copy, 'condition'));

                if (! $condition) {
                    throw ValidationException::withMessages(['copies.'.$index.'.condition' => trans('validation.exists', ['attribute' => trans('library.book_condition.book_condition')])]);
                }

                $book = $existingBooks->firstWhere('uuid', Arr::get($copy, 'book.uuid'));

                if (! $book) {
                    throw ValidationException::withMessages(['copies.'.$index.'.book' => trans('validation.exists', ['attribute' => trans('library.book.book')])]);
                }

                $newCopies[] = [
                    'uuid' => Arr::get($copy, 'uuid', (string) Str::uuid()),
                    'number' => Arr::get($copy, 'number'),
                    'condition_id' => $condition?->id,
                    'book_id' => $book?->id,
                    'vendor' => Arr::get($copy, 'vendor'),
                    'invoice_number' => Arr::get($copy, 'invoice_number'),
                    'invoice_date' => Arr::get($copy, 'invoice_date'),
                    'room_number' => Arr::get($copy, 'room_number'),
                    'rack_number' => Arr::get($copy, 'rack_number'),
                    'shelf_number' => Arr::get($copy, 'shelf_number'),
                ];
            }

            $this->merge([
                'book_id' => $book?->id,
                'copies' => $newCopies,
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
            'date' => __('library.book_addition.props.date'),
            'copies' => __('library.book_addition.props.copies'),
            'copies.*.book' => __('library.book.book'),
            'copies.*.number' => __('library.book_addition.props.number'),
            'copies.*.condition' => __('library.book_condition.book_condition'),
            'copies.*.vendor' => __('library.book_addition.props.vendor'),
            'copies.*.invoice_number' => __('library.book_addition.props.invoice_number'),
            'copies.*.invoice_date' => __('library.book_addition.props.invoice_date'),
            'copies.*.room_number' => __('library.book_addition.props.room_number'),
            'copies.*.rack_number' => __('library.book_addition.props.rack_number'),
            'copies.*.shelf_number' => __('library.book_addition.props.shelf_number'),
            'remarks' => __('library.book_addition.props.remarks'),
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
