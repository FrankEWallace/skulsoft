<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\LedgerType;
use Illuminate\Foundation\Http\FormRequest;

class LedgerTypeRequest extends FormRequest
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
            'name' => 'required|min:2|max:100',
            'alias' => 'nullable|min:2|max:50',
            'description' => 'nullable|max:1000',
            'parent' => 'required|uuid',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $uuid = $this->route('ledger_type.uuid');

            $existingLedgerTypes = LedgerType::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingLedgerTypes) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('finance.ledger_type.ledger_type')]));
            }

            $this->whenFilled('alias', function (string $input) use ($validator, $uuid) {
                $existingLedgerTypeAliases = LedgerType::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereAlias($input)
                    ->exists();

                if ($existingLedgerTypeAliases) {
                    $validator->errors()->add('alias', trans('validation.unique', ['attribute' => trans('finance.ledger_type.ledger_type')]));
                }
            });

            $parent = LedgerType::findByUuidOrFail($this->parent, 'parent');

            $this->merge([
                'parent' => $parent,
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
            'name' => __('finance.ledger_type.props.name'),
            'alias' => __('finance.ledger_type.props.alias'),
            'parent' => __('finance.ledger_type.props.parent'),
            'description' => __('finance.ledger_type.props.description'),
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
