<?php

namespace App\Http\Requests\Academic;

use App\Models\Academic\Period;
use App\Models\Academic\Session;
use Illuminate\Foundation\Http\FormRequest;

class PeriodRequest extends FormRequest
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
            'session' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'shortcode' => ['nullable', 'string', 'max:50'],
            'alias' => ['nullable', 'string', 'max:50'],
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'seeders' => ['nullable', 'array'],
            'description' => 'nullable|string|max:1000',
        ];

        if (config('config.student.enable_timesheet')) {
            $rules['session_start_time'] = ['required', 'date_format:H:i:s'];
            $rules['session_end_time'] = ['required', 'date_format:H:i:s', 'after_or_equal:session_start_time'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('period');

            $session = $this->input('session') ? Session::query()
                ->byTeam()
                ->where('uuid', $this->input('session'))
                ->getOrFail(trans('academic.session.session')) : null;

            $existingRecords = Period::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereSessionId($session?->id)
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.period.period')]));
            }

            $this->whenFilled('code', function (string $input) use ($validator, $session, $uuid) {
                $existingCodes = Period::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereSessionId($session?->id)
                    ->whereCode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('academic.period.period')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) {
                // Can have duplicate shortcodes
                // $existingShortcodes = Period::query()
                //     ->byTeam()
                //     ->when($uuid, function ($q, $uuid) {
                //         $q->where('uuid', '!=', $uuid);
                //     })
                //     ->whereSessionId($session?->id)
                //     ->whereShortcode($input)
                //     ->exists();

                // if ($existingShortcodes) {
                //     $validator->errors()->add('shortcode', trans('validation.unique', ['attribute' => trans('academic.period.period')]));
                // }
            });

            $this->whenFilled('alias', function (string $input) use ($validator, $session, $uuid) {
                $existingAliases = Period::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereSessionId($session?->id)
                    ->whereAlias($input)
                    ->exists();

                if ($existingAliases) {
                    $validator->errors()->add('alias', trans('validation.unique', ['attribute' => trans('academic.period.period')]));
                }
            });

            $periodCount = Period::query()
                ->byTeam()
                ->count();

            if ($this->method() === 'POST') {
                $file = resource_path('var/academic-seeder.json');
                $seeder = (\File::exists($file)) ? \File::json($file) : [];

                $inputSeeders = collect($seeder)->whereIn('code', $this->input('seeders', []))->pluck('country')->unique()->toArray();

                if (count($inputSeeders) > 1) {
                    $validator->errors()->add('seeders', trans('academic.period.multiple_country_seeder'));
                }
            }

            $this->merge([
                'session_id' => $session?->id,
                'is_default' => $periodCount === 0 ? true : false,
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
            'session' => __('academic.session.session'),
            'name' => __('academic.period.props.name'),
            'code' => __('academic.period.props.code'),
            'shortcode' => __('academic.period.props.shortcode'),
            'alias' => __('academic.period.props.alias'),
            'start_date' => __('academic.period.props.start_date'),
            'end_date' => __('academic.period.props.end_date'),
            'session_start_time' => __('academic.period.props.session_start_time'),
            'session_end_time' => __('academic.period.props.session_end_time'),
            'description' => __('academic.period.props.description'),
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
