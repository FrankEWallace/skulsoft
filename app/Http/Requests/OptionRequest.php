<?php

namespace App\Http\Requests;

use App\Enums\OptionType;
use App\Models\Option;
use App\Support\OptionAdditionalRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class OptionRequest extends FormRequest
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
        $types = implode(',', Arr::pluck(OptionType::getOptions(), 'value'));

        return [
            'name' => 'required|min:1|max:100',
            'color' => ['sometimes', 'required', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'type' => 'required|in:'.$types,
            'description' => 'nullable|max:500',
            ...OptionAdditionalRequest::getDetail($this->type),
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('option.uuid');

            $optionQuery = Option::query();

            $existingOptions = $optionQuery->when($uuid, function ($q, $uuid) {
                $q->where('uuid', '!=', $uuid);
            })->when($this->team, function ($q) {
                $q->whereTeamId(auth()->user()?->current_team_id);
            })->whereName($this->name)->whereType($this->type)->count();

            if ($existingOptions) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => $this->trans]));
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
            'type' => __('option.props.type'),
            'name' => __('option.props.name'),
            'color' => __('option.props.color'),
            ...OptionAdditionalRequest::getTransAttributes($this->type),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [...OptionAdditionalRequest::getDetail($this->type, 'messages')];
    }
}
