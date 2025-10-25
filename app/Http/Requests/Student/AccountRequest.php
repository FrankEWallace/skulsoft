<?php

namespace App\Http\Requests\Student;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
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
        $rules = [
            'name' => 'required|min:2|max:100',
            'alias' => 'nullable|min:2|max:100',
            'number' => 'required|min:2|max:100',
            'bank_name' => 'required|min:2|max:100',
            'branch_name' => 'required|min:2|max:100',
        ];

        if (config('config.finance.enable_bank_code1')) {
            $rules['bank_code1'] = [config('config.finance.is_bank_code1_required') ? 'required' : 'nullable', 'min:2', 'max:100'];
        }

        if (config('config.finance.enable_bank_code2')) {
            $rules['bank_code2'] = [config('config.finance.is_bank_code2_required') ? 'required' : 'nullable', 'min:2', 'max:100'];
        }

        if (config('config.finance.enable_bank_code3')) {
            $rules['bank_code3'] = [config('config.finance.is_bank_code3_required') ? 'required' : 'nullable', 'min:2', 'max:100'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $studentUuid = $this->route('student');
            $accountUuid = $this->route('account');

            $student = Student::query()
                ->whereUuid($studentUuid)
                ->firstOrFail();

            $existingAccount = Account::whereHasMorph(
                'accountable', [Contact::class],
                function ($q) use ($student) {
                    $q->whereId($student->contact_id);
                }
            )
                ->when($accountUuid, function ($q, $accountUuid) {
                    $q->where('uuid', '!=', $accountUuid);
                })
                ->whereNumber($this->number)
                ->exists();

            if ($existingAccount) {
                $validator->errors()->add('number', trans('validation.unique', ['attribute' => __('finance.account.props.number')]));
            }

            if ($this->alias) {
                $existingAlias = Account::whereHasMorph(
                    'accountable', [Contact::class],
                    function ($q) use ($student) {
                        $q->whereId($student->contact_id);
                    }
                )
                    ->when($accountUuid, function ($q, $accountUuid) {
                        $q->where('uuid', '!=', $accountUuid);
                    })
                    ->whereAlias($this->alias)
                    ->exists();

                if ($existingAlias) {
                    $validator->errors()->add('alias', trans('validation.unique', ['attribute' => __('finance.account.props.alias')]));
                }
            }
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
            'name' => __('finance.account.props.name'),
            'alias' => __('finance.account.props.alias'),
            'number' => __('finance.account.props.number'),
            'bank_name' => __('finance.account.props.bank_name'),
            'branch_name' => __('finance.account.props.branch_name'),
            'bank_code1' => config('config.finance.bank_code1_label'),
            'bank_code2' => config('config.finance.bank_code2_label'),
            'bank_code3' => config('config.finance.bank_code3_label'),
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
