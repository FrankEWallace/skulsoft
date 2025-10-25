<?php

namespace App\Http\Requests\Recruitment;

use App\Enums\OptionType;
use App\Models\Employee\Designation;
use App\Models\Option;
use App\Models\Recruitment\Vacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class VacancyRequest extends FormRequest
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
            'title' => 'required|min:5|max:255',
            'records' => 'required|array|min:1',
            'records.*.employment_type' => 'required|uuid',
            'records.*.designation' => 'required|uuid',
            'records.*.number_of_positions' => 'required|integer|min:1',
            'last_application_date' => 'required|date_format:Y-m-d',
            'description' => 'required|min:10|max:10000',
            'responsibility' => 'nullable|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Vacancy)->getModelName();

            $vacancyUuid = $this->route('vacancy');

            $existingTitle = Vacancy::query()
                ->where('title', $this->title)
                ->where('team_id', auth()->user()->current_team_id)
                ->when($vacancyUuid, function ($query) use ($vacancyUuid) {
                    return $query->where('uuid', '!=', $vacancyUuid);
                })
                ->exists();

            if ($existingTitle) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => trans('recruitment.vacancy.props.title')]));
            }

            $designations = Designation::query()
                ->select('id', 'uuid')
                ->byTeam()
                ->get();

            $employmentTypes = Option::query()
                ->select('id', 'uuid')
                ->byTeam()
                ->where('type', OptionType::EMPLOYMENT_TYPE->value)
                ->get();

            $newRecords = [];

            foreach ($this->records as $index => $record) {
                $designation = $designations->firstWhere('uuid', Arr::get($record, 'designation'));
                $employmentType = $employmentTypes->firstWhere('uuid', Arr::get($record, 'employment_type'));

                if (! $designation) {
                    $validator->errors()->add('records.'.$index.'.designation', trans('global.could_not_find', ['attribute' => trans('employee.designation.designation')]));
                }

                if (! $employmentType) {
                    $validator->errors()->add('records.'.$index.'.employment_type', trans('global.could_not_find', ['attribute' => trans('employee.employment_type.employment_type')]));
                }

                $newRecords[] = [
                    'designation_id' => $designation->id,
                    'employment_type_id' => $employmentType->id,
                    'number_of_positions' => Arr::get($record, 'number_of_positions'),
                ];
            }

            $this->merge([
                'records' => $newRecords,
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
            'title' => __('recruitment.vacancy.props.title'),
            'records.*.designation' => __('employee.designation.designation'),
            'records.*.number_of_positions' => __('recruitment.vacancy.props.number_of_positions'),
            'records.*.employment_type' => __('employee.employment_type.employment_type'),
            'last_application_date' => __('recruitment.vacancy.props.last_application_date'),
            'description' => __('recruitment.vacancy.props.description'),
            'responsibility' => __('recruitment.vacancy.props.responsibility'),
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
