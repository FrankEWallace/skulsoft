<?php

namespace App\Http\Requests\Resource;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Models\Media;
use App\Models\Resource\Download;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class DownloadRequest extends FormRequest
{
    use HasAudience;

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
            'title' => 'required|max:255',
            'student_audience_type' => [new Enum(StudentAudienceType::class)],
            'student_audiences' => 'array|required_if:student_audience_type,division_wise,course_wise,batch_wise',
            'employee_audience_type' => [new Enum(EmployeeAudienceType::class)],
            'employee_audiences' => 'array|required_if:employee_audience_type,department_wise,designation_wise',
            'is_public' => 'boolean',
            'expires_at' => 'nullable|date_format:Y-m-d H:i:s',
            'description' => 'nullable|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Download)->getModelName();

            $downloadUuid = $this->route('download');

            $data = $this->validateInput($this->all());

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->where(function ($q) use ($downloadUuid) {
                    $q->whereStatus(0)
                        ->when($downloadUuid, function ($q) {
                            $q->orWhere('status', 1);
                        });
                })
                ->exists();

            if (! $attachedMedia) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
            }

            $this->merge([
                'student_audience_type' => Arr::get($data, 'studentAudienceType'),
                'student_audiences' => Arr::get($data, 'studentAudiences'),
                'employee_audience_type' => Arr::get($data, 'employeeAudienceType'),
                'employee_audiences' => Arr::get($data, 'employeeAudiences'),
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
            'title' => __('resource.download.props.title'),
            'description' => __('resource.download.props.description'),
            'is_public' => __('resource.download.props.is_public'),
            'expires_at' => __('resource.download.props.expires_at'),
            'student_audience_type' => __('resource.download.props.audience'),
            'employee_audience_type' => __('resource.download.props.audience'),
            'student_audiences' => __('resource.download.props.audience'),
            'employee_audiences' => __('resource.download.props.audience'),
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
            'student_audiences.required_if' => __('validation.required', ['attribute' => trans('resource.download.props.audience')]),
            'employee_audiences.required_if' => __('validation.required', ['attribute' => trans('resource.download.props.audience')]),
        ];
    }
}
