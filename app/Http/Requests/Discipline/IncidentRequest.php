<?php

namespace App\Http\Requests\Discipline;

use App\Enums\Discipline\IncidentNature;
use App\Enums\Discipline\IncidentSeverity;
use App\Enums\OptionType;
use App\Models\Discipline\Incident;
use App\Models\Option;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IncidentRequest extends FormRequest
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
            'category' => 'required|uuid',
            'title' => 'required|max:255',
            'nature' => ['required', new Enum(IncidentNature::class)],
            'severity' => ['nullable', new Enum(IncidentSeverity::class)],
            'date' => 'required|date_format:Y-m-d',
            'reported_by' => 'required|max:255',
            'student' => 'required|uuid',
            'description' => 'nullable|max:1000',
            'action' => 'nullable|max:1000',
            'remarks' => 'nullable|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Incident)->getModelName();

            $incidentUuid = $this->route('incident');

            $category = $this->category ? Option::query()
                ->byTeam()
                ->whereType(OptionType::INCIDENT_CATEGORY->value)
                ->whereUuid($this->category)
                ->getOrFail(__('discipline.incident.category.category'), 'category') : null;

            $model = Student::query()
                ->byTeam()
                ->whereUuid($this->student)
                ->getOrFail(__('student.student'), 'student');

            $this->merge([
                'category_id' => $category?->id,
                'model_id' => $model?->id,
                'model_type' => 'Student',
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
            'category' => __('discipline.incident.category.category'),
            'title' => __('discipline.incident.props.title'),
            'nature' => __('discipline.incident.props.nature'),
            'severity' => __('discipline.incident.props.severity'),
            'date' => __('discipline.incident.props.date'),
            'reported_by' => __('discipline.incident.props.reported_by'),
            'student' => __('student.student'),
            'description' => __('discipline.incident.props.description'),
            'action' => __('discipline.incident.props.action'),
            'remarks' => __('discipline.incident.props.remarks'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
