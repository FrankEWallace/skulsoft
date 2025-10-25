<?php

namespace App\Http\Requests\Recruitment\Job;

use App\Concerns\SimpleValidation;
use App\Enums\Gender;
use App\Models\Employee\Designation;
use App\Models\Media;
use App\Models\Recruitment\Application;
use App\Models\Recruitment\Vacancy;
use App\Rules\AlphaSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class ApplicationRequest extends FormRequest
{
    use SimpleValidation;

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
        $option = request()->query('option');

        $rules = [
            'designation' => 'required|uuid',
            'first_name' => ['required', 'min:2', 'max:100', new AlphaSpace],
            'middle_name' => ['nullable', 'max:100', new AlphaSpace],
            'third_name' => ['nullable', 'max:100', new AlphaSpace],
            'last_name' => ['nullable', 'max:100', new AlphaSpace],
            'birth_date' => 'required|date_format:Y-m-d',
            'gender' => ['required', new Enum(Gender::class)],
            'father_name' => ['nullable', 'max:100', new AlphaSpace],
            'mother_name' => ['nullable', 'max:100', new AlphaSpace],
        ];

        if ($option == 'contact') {
            $rules['contact_number'] = 'required|min:4|max:20';
            $rules['email'] = 'nullable|email|max:100';
            $rules['present_address.address_line1'] = 'required|min:2|max:100';
            $rules['present_address.address_line2'] = 'nullable|min:2|max:100';
            $rules['present_address.city'] = 'required|min:2|max:100';
            $rules['present_address.state'] = 'required|min:2|max:100';
            $rules['present_address.zipcode'] = 'required|min:2|max:100';
            $rules['present_address.country'] = 'required|min:2|max:100';
        }

        if ($option == 'cover_letter') {
            $rules['availability_date'] = 'required|date_format:Y-m-d|after:today';
            $rules['qualification_summary'] = 'required|min:2|max:100';
            $rules['cover_letter'] = 'required|min:2|max:1000';
        }

        if ($option == 'upload') {
            $mediaModel = (new Application)->getModelName();

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->get();

            $this->validateUpload($attachedMedia, 'resume');

            $this->validateUpload($attachedMedia, 'marksheet');

            $this->validateUpload($attachedMedia, 'id_proof');

            $this->validateUpload($attachedMedia, 'address_proof');
        }

        if ($option == 'finish') {
            $rules['declaration'] = 'accepted';
        }

        return $rules;
    }

    private function validateUpload(Collection $media, string $section)
    {
        if (! $media->where('meta.section', $section)->count()) {
            throw ValidationException::withMessages([$section => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
        }
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            $validator->after(function ($validator) {
                $this->change($validator, 'present_address.address_line1', 'present_address_address_line1');
                $this->change($validator, 'present_address.address_line2', 'present_address_address_line2');
                $this->change($validator, 'present_address.city', 'present_address_city');
                $this->change($validator, 'present_address.state', 'present_address_state');
                $this->change($validator, 'present_address.zipcode', 'present_address_zipcode');
                $this->change($validator, 'present_address.country', 'present_address_country');
            });

            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Application)->getModelName();

            $vacancy = Vacancy::query()
                ->with('records')
                ->whereSlug($this->route('slug'))
                ->where('last_application_date', '>=', today()->toDateString())
                ->where('published_at', '<=', now()->toDateTimeString())
                ->firstOrFail();

            $designations = $vacancy->records->pluck('designation_id')->all();

            $designation = Designation::query()
                ->whereTeamId($vacancy->team_id)
                ->whereIn('id', $designations)
                ->whereUuid($this->designation)
                ->getOrFail(trans('employee.designation.designation'));

            $this->merge([
                'vacancy' => $vacancy,
                'designation_id' => $designation?->id,
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
            'vacancy' => __('recruitment.vacancy.vacancy'),
            'designation' => __('employee.designation.designation'),
            'first_name' => __('contact.props.first_name'),
            'middle_name' => __('contact.props.middle_name'),
            'third_name' => __('contact.props.third_name'),
            'last_name' => __('contact.props.last_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'father_name' => __('contact.props.father_name'),
            'mother_name' => __('contact.props.mother_name'),
            'contact_number' => __('contact.props.contact_number'),
            'email' => __('contact.props.email'),
            'present_address.address_line1' => __('contact.props.address.address_line1'),
            'present_address.address_line2' => __('contact.props.address.address_line2'),
            'present_address.city' => __('contact.props.address.city'),
            'present_address.state' => __('contact.props.address.state'),
            'present_address.zipcode' => __('contact.props.address.zipcode'),
            'present_address.country' => __('contact.props.address.country'),
            'qualification_summary' => __('recruitment.application.props.qualification_summary'),
            'cover_letter' => __('recruitment.application.props.cover_letter'),
            'declaration' => __('recruitment.vacancy.wizard.declaration'),
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
