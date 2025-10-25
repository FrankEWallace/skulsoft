<?php

namespace App\Http\Requests\Library;

use App\Enums\Library\ReturnStatus;
use App\Enums\OptionType;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BookReturnRequest extends FormRequest
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
            'number' => ['required'],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'return_status' => ['required', new Enum(ReturnStatus::class)],
            'condition' => ['nullable', 'uuid'],
            'library_charge' => ['numeric', 'min:0'],
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

            $condition = $this->condition ? Option::query()
                ->where('uuid', $this->condition)
                ->where('type', OptionType::BOOK_CONDITION)
                ->getOrFail(trans('library.book_condition.book_condition'), 'condition') : null;

            $this->merge([
                'condition_id' => $condition?->id,
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
            'number' => __('library.book.props.number'),
            'return_date' => __('library.transaction.props.return_date'),
            'return_status' => __('library.transaction.props.return_status'),
            'condition' => __('library.book_condition.book_condition'),
            'library_charge' => __('library.transaction.props.library_charge'),
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
