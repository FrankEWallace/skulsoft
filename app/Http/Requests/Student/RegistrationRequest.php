<?php

namespace App\Http\Requests\Student;

use App\Concerns\CustomFormFieldValidation;
use App\Enums\CustomFieldForm;
use App\Enums\FamilyRelation;
use App\Enums\Gender;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Guardian;
use App\Models\Option;
use App\Rules\AlphaSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class RegistrationRequest extends FormRequest
{
    use CustomFormFieldValidation;

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
            'student_type' => 'required|in:new,existing',
            'date' => 'required|date_format:Y-m-d',
            'period' => 'required|uuid',
            'course' => 'required|uuid',
            'enrollment_type' => 'nullable|uuid',
        ];

        if ($this->student_type == 'new') {
            $rules['first_name'] = ['required', 'min:2', 'max:100', new AlphaSpace];
            $rules['last_name'] = ['max:100', new AlphaSpace];
            $rules['gender'] = ['required', new Enum(Gender::class)];
            $rules['birth_date'] = 'required|date_format:Y-m-d|before:today';
            $rules['contact_number'] = 'required|min:4|max:20';
            $rules['guardians'] = 'array';
            $rules['guardians.*.guardian_type'] = ['required', 'in:new,existing'];
            $rules['guardians.*.guardian'] = ['required_if:guardians.*.type,existing'];
            $rules['guardians.*.name'] = ['nullable', 'required_if:guardians.*.guardian_type,new', 'min:2', 'max:100', new AlphaSpace];
            $rules['guardians.*.relation'] = ['required_if:guardians.*.guardian_type,new', new Enum(FamilyRelation::class)];
            $rules['guardians.*.contact_number'] = 'nullable|required_if:guardians.*.guardian_type,new|min:4|max:20';
        } else {
            $rules['student'] = 'required|uuid';
        }

        if (config('config.contact.enable_middle_name_field')) {
            $rules['middle_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        if (config('config.contact.enable_third_name_field')) {
            $rules['third_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('registration');

            $period = Period::query()
                ->byTeam()
                ->whereUuid($this->period)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('academic.period.period')]), 'period');

            $course = Course::query()
                ->byPeriod($period->id)
                ->filterAccessible()
                ->whereUuid($this->course)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('academic.course.course')]), 'course');

            $enrollmentType = $this->enrollment_type ? Option::query()
                ->byTeam()
                ->whereUuid($this->enrollment_type)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('student.enrollment_type.enrollment_type')]), 'enrollment_type') : null;

            if (! $course->enable_registration) {
                $validator->errors()->add('course', trans('academic.course.registration_disabled_info'));
            }

            if ($this->student_type == 'existing') {
                $contact = Contact::query()
                    ->byTeam()
                    ->whereHas('students', function ($q) {
                        $q->whereUuid($this->student);
                    })
                    ->getOrFail(trans('global.could_not_find', ['attribute' => trans('student.student')]), 'student');

                $this->merge([
                    'contact_id' => $contact->id,
                    'guardians' => [],
                ]);
            } else {
                $guardians = [];
                foreach ($this->guardians as $index => $guardian) {
                    $guardianType = Arr::get($guardian, 'guardian_type');

                    if ($guardianType == 'existing') {
                        $existingGuardian = Guardian::query()
                            ->with('contact', 'primary')
                            ->whereHas('contact', function ($q) {
                                $q->whereTeamId(auth()->user()?->current_team_id);
                            })
                            ->whereUuid(Arr::get($guardian, 'guardian'))
                            ->first();

                        if (! $existingGuardian) {
                            $validator->errors()->add('guardians.'.$index.'.guardian', trans('global.could_not_find', ['attribute' => trans('guardian.guardian')]));
                        } else {
                            $guardian['guardian_id'] = $existingGuardian->contact->id;
                        }
                    }

                    $guardians[] = $guardian;
                }

                $this->merge(['guardians' => $guardians]);
            }

            $customFields = CustomField::query()
                ->byTeam()
                ->whereForm(CustomFieldForm::REGISTRATION)
                ->get();

            $newCustomFields = $this->validateFields($validator, $customFields, $this->input('custom_fields'));

            $this->merge([
                'period_id' => $period?->id,
                'course_id' => $course?->id,
                'program_id' => $course?->division?->program_id,
                'enrollment_type_id' => $enrollmentType?->id,
                'registration_fee' => $course->enable_registration ? $course->registration_fee->value : 0,
                'custom_fields' => $newCustomFields,
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
            'period' => __('academic.period.period'),
            'course' => __('academic.course.course'),
            'enrollment_type' => __('student.enrollment_type.enrollment_type'),
            'date' => __('student.registration.props.date'),
            'code_number' => __('student.registration.props.code_number'),
            'first_name' => __('contact.props.first_name'),
            'middle_name' => __('contact.props.middle_name'),
            'third_name' => __('contact.props.third_name'),
            'last_name' => __('contact.props.last_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'contact_number' => __('contact.props.contact_number'),
            'guardians.*.name' => __('guardian.props.name'),
            'guardians.*.relation' => __('contact.props.relation'),
            'guardians.*.contact_number' => __('contact.props.contact_number'),
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
            'guardians.*.name.required_if' => __('validation.required', ['attribute' => __('guardian.props.name')]),
            'guardians.*.relation.required_if' => __('validation.required', ['attribute' => __('contact.props.relation')]),
            'guardians.*.contact_number.required_if' => __('validation.required', ['attribute' => __('contact.props.contact_number')]),
        ];
    }
}
