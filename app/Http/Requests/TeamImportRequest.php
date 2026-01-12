<?php

namespace App\Http\Requests;

use App\Domain\Academic\Models\Period;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class TeamImportRequest extends FormRequest
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
            'team' => ['required', 'uuid'],
            'period' => ['nullable', 'uuid'],
            'module' => ['required', 'in:academic,student,employee,finance,contact,transport,approval'],
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $currentTeam = $this->route('team');

            $team = Team::query()
                ->where('uuid', $this->team)
                ->getOrFail(trans('team.team'));

            if ($team->id == $currentTeam->id) {
                $validator->errors()->add('team', trans('validation.different', ['attribute' => trans('team.team'), 'other' => trans('team.current_team')]));
            }

            $periods = Period::query()
                ->where('team_id', $team->id)
                ->get();

            if ($periods->isEmpty()) {
                $validator->errors()->add('period', trans('global.could_not_find', ['attribute' => trans('academic.period.period')]));
            }

            if ($periods->count() > 1 && empty($this->period)) {
                $validator->errors()->add('period', trans('validation.required', ['attribute' => trans('academic.period.period')]));
            }

            if ($periods->count() == 1 && ! empty($this->period) && ! $periods->firstWhere('uuid', $this->period)) {
                $validator->errors()->add('period', trans('global.could_not_find', ['attribute' => trans('academic.period.period')]));
            }

            $this->merge([
                'team_id' => $team?->id,
                'period_id' => $periods->firstWhere('uuid', $this->period)?->id,
            ]);

            if ($periods->count() == 1 && empty($this->period)) {
                $this->merge([
                    'period_id' => $periods->first()?->id,
                ]);
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
            'team' => __('team.team'),
            'period' => __('academic.period.period'),
            'module' => __('module.module'),
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
