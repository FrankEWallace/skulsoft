<?php

namespace App\Http\Requests\Student;

use App\Enums\OptionType;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Media;
use App\Models\Option;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DocumentRequest extends FormRequest
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
            'type' => 'required',
            'title' => 'required|min:2|max:100',
            'description' => 'nullable|min:2|max:1000',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $studentUuid = $this->route('student');
            $documentUuid = $this->route('document');

            $student = Student::query()
                ->whereUuid($studentUuid)
                ->firstOrFail();

            $mediaModel = (new Document)->getModelName();

            $documentType = Option::query()
                ->byTeam()
                ->whereType(OptionType::STUDENT_DOCUMENT_TYPE->value)
                ->whereUuid($this->type)
                ->getOrFail(__('student.document_type.document_type'), 'type');

            $existingDocument = Document::whereHasMorph(
                'documentable', [Contact::class],
                function ($q) use ($student) {
                    $q->whereId($student->contact_id);
                }
            )
                ->when($documentUuid, function ($q, $documentUuid) {
                    $q->where('uuid', '!=', $documentUuid);
                })
                ->whereTypeId($documentType->id)
                ->whereTitle($this->title)
                ->exists();

            if ($existingDocument) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('student.document.props.title')]));
            }

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->where(function ($q) use ($documentUuid) {
                    $q->whereStatus(0)
                        ->when($documentUuid, function ($q) {
                            $q->orWhere('status', 1);
                        });
                })
                ->exists();

            if (! $attachedMedia) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
            }

            $this->merge([
                'type_id' => $documentType->id,
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
            'title' => __('student.document.props.title'),
            'description' => __('student.document.props.description'),
            'start_date' => __('student.document.props.start_date'),
            'end_date' => __('student.document.props.end_date'),
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
