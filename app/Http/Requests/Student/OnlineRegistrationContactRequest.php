<?php

namespace App\Http\Requests\Student;

use App\Concerns\SimpleValidation;
use App\Models\Media;
use App\Models\Student\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OnlineRegistrationContactRequest extends FormRequest
{
    use SimpleValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'alternate_records.contact_number' => 'sometimes|min:2|max:100',
            'alternate_records.email' => 'sometimes|nullable|email|min:2|max:100',
            'present_address.address_line1' => 'sometimes|required|min:2|max:100',
            'present_address.address_line2' => 'sometimes|nullable|min:2|max:100',
            'present_address.city' => 'sometimes|nullable|min:2|max:100',
            'present_address.state' => 'sometimes|nullable|min:2|max:100',
            'present_address.zipcode' => 'sometimes|nullable|min:2|max:100',
            'present_address.country' => 'sometimes|required|min:2|max:100',
            'permanent_address.same_as_present_address' => 'sometimes|boolean',
            'permanent_address.address_line1' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.address_line2' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.city' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.state' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.zipcode' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.country' => 'sometimes|nullable|min:2|max:100',
        ];

        return $rules;
    }

    private function validateUpload(Collection $media, string $section)
    {
        $mandatoryUploadFields = explode(',', config('config.feature.online_registration_mandatory_upload_field'));

        if (! in_array($section, $mandatoryUploadFields)) {
            return;
        }

        if (! $media->where('meta.section', $section)->count()) {
            throw ValidationException::withMessages([$section => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
        }
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            $validator->after(function ($validator) {
                $this->change($validator, 'alternate_records.email', 'alternate_email');
                $this->change($validator, 'alternate_records.mobile', 'alternate_contact_number');
                $this->change($validator, 'present_address.address_line1', 'present_address_address_line1');
                $this->change($validator, 'present_address.address_line2', 'present_address_address_line2');
                $this->change($validator, 'present_address.city', 'present_address_city');
                $this->change($validator, 'present_address.state', 'present_address_state');
                $this->change($validator, 'present_address.zipcode', 'present_address_zipcode');
                $this->change($validator, 'present_address.country', 'present_address_country');
                $this->change($validator, 'permanent_address.address_line1', 'permanent_address_address_line1');
                $this->change($validator, 'permanent_address.address_line2', 'permanent_address_address_line2');
                $this->change($validator, 'permanent_address.city', 'permanent_address_city');
                $this->change($validator, 'permanent_address.state', 'permanent_address_state');
                $this->change($validator, 'permanent_address.zipcode', 'permanent_address_zipcode');
                $this->change($validator, 'permanent_address.country', 'permanent_address_country');
            });

            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Registration)->getModelName();

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->get();

            $this->validateUpload($attachedMedia, 'id_proof');

            $this->validateUpload($attachedMedia, 'address_proof');
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
            'alternate_records.contact_number' => __('contact.props.alternate_contact_number'),
            'alternate_records.email' => __('contact.props.alternate_email'),
            'present_address.address_line1' => __('contact.props.address.address_line1'),
            'present_address.address_line2' => __('contact.props.address.address_line2'),
            'present_address.city' => __('contact.props.address.city'),
            'present_address.state' => __('contact.props.address.state'),
            'present_address.zipcode' => __('contact.props.address.zipcode'),
            'present_address.country' => __('contact.props.address.country'),
            'permanent_address.same_as_present_address' => __('contact.props.address.same_as_present_address'),
            'permanent_address.address_line1' => __('contact.props.address.address_line1'),
            'permanent_address.address_line2' => __('contact.props.address.address_line2'),
            'permanent_address.city' => __('contact.props.address.city'),
            'permanent_address.state' => __('contact.props.address.state'),
            'permanent_address.zipcode' => __('contact.props.address.zipcode'),
            'permanent_address.country' => __('contact.props.address.country'),
        ];
    }
}
