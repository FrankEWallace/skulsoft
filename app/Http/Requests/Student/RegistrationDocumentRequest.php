<?php

namespace App\Http\Requests\Student;

use App\Enums\OptionType;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Option;
use App\Models\Student\Registration;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationDocumentRequest extends FormRequest
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
            'description' => 'nullable|min:2|max:200',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $registrationUuid = $this->route('registration');
            $documentUuid = $this->route('document');

            $registration = Registration::query()
                ->whereUuid($registrationUuid)
                ->firstOrFail();

            $documentType = Option::query()
                ->byTeam()
                ->whereType(OptionType::STUDENT_DOCUMENT_TYPE->value)
                ->whereUuid($this->type)
                ->getOrFail(__('student.document_type.document_type'), 'type');

            $existingDocument = Document::whereHasMorph(
                'documentable', [Contact::class],
                function ($q) use ($registration) {
                    $q->whereId($registration->contact_id);
                }
            )
                ->when($documentUuid, function ($q, $documentUuid) {
                    $q->where('uuid', '!=', $documentUuid);
                })
                ->whereTitle($this->title)
                ->exists();

            if ($existingDocument) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('student.document.props.title')]));
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
            'type' => __('student.document_type.document_type'),
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
