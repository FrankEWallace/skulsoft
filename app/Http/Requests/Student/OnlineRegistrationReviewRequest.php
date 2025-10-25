<?php

namespace App\Http\Requests\Student;

use App\Models\Media;
use App\Models\Student\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OnlineRegistrationReviewRequest extends FormRequest
{
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
            'date_of_submission' => 'required',
            'place_of_submission' => 'required|min:2|max:100',
            'declaration' => 'required|accepted',
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
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Registration)->getModelName();

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->get();

            $this->validateUpload($attachedMedia, 'signature');
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
            //
        ];
    }
}
