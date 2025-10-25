<?php

namespace App\Http\Requests\Hostel;

use App\Models\Hostel\Block;
use Illuminate\Foundation\Http\FormRequest;

class BlockRequest extends FormRequest
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
            'alias' => 'nullable|min:2|max:100',
            'contact_number' => 'required|min:2|max:100',
            'contact_email' => 'nullable|email|min:2|max:100',
            'address' => 'required|min:2|max:100',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('block');

            $existingNames = Block::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('hostel.block.block')]));
            }

            if (! $this->alias) {
                return;
            }

            $existingAliases = Block::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereAlias($this->alias)
                ->exists();

            if ($existingAliases) {
                $validator->errors()->add('alias', trans('validation.unique', ['attribute' => __('hostel.block.block')]));
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
            'name' => __('hostel.block.props.name'),
            'alias' => __('hostel.block.props.alias'),
            'contact_number' => __('hostel.block.props.contact_number'),
            'contact_email' => __('hostel.block.props.contact_email'),
            'address' => __('hostel.block.props.address'),
            'description' => __('hostel.block.props.description'),
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
