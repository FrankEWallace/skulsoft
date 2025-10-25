<?php

namespace App\Http\Requests\Academic;

use App\Models\Academic\Session;
use Illuminate\Foundation\Http\FormRequest;

class SessionRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'shortcode' => ['nullable', 'string', 'max:50'],
            'alias' => ['nullable', 'string', 'max:50'],
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'description' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('session');

            $existingRecords = Session::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.session.session')]));
            }

            $this->whenFilled('code', function (string $input) use ($validator, $uuid) {
                $existingCodes = Session::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereCode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('academic.session.session')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) {
                // Can have duplicate shortcodes
                // $existingShortcodes = Session::query()
                //     ->byTeam()
                //     ->when($uuid, function ($q, $uuid) {
                //         $q->where('uuid', '!=', $uuid);
                //     })
                //     ->whereShortcode($input)
                //     ->exists();

                // if ($existingShortcodes) {
                //     $validator->errors()->add('shortcode', trans('validation.unique', ['attribute' => trans('academic.session.session')]));
                // }
            });

            $this->whenFilled('alias', function (string $input) use ($validator, $uuid) {
                $existingAliases = Session::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereAlias($input)
                    ->exists();

                if ($existingAliases) {
                    $validator->errors()->add('alias', trans('validation.unique', ['attribute' => trans('academic.session.session')]));
                }
            });

            $this->merge([
                'is_default' => false,
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
            'name' => __('academic.session.props.name'),
            'code' => __('academic.session.props.code'),
            'shortcode' => __('academic.session.props.shortcode'),
            'alias' => __('academic.session.props.alias'),
            'start_date' => __('academic.session.props.start_date'),
            'end_date' => __('academic.session.props.end_date'),
            'description' => __('academic.session.props.description'),
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
