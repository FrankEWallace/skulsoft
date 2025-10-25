<?php

namespace App\Http\Requests\Student;

use App\Enums\OptionType;
use App\Models\Contact;
use App\Models\Dialogue;
use App\Models\Media;
use App\Models\Option;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DialogueRequest extends FormRequest
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
            'category' => 'nullable|uuid',
            'title' => 'required|min:2|max:100',
            'description' => 'nullable|min:2|max:1000',
            'date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $studentUuid = $this->route('student');
            $dialogueUuid = $this->route('dialogue');

            $student = Student::query()
                ->whereUuid($studentUuid)
                ->firstOrFail();

            $mediaModel = (new Dialogue)->getModelName();

            $category = $this->category ? Option::query()
                ->byTeam()
                ->whereType(OptionType::STUDENT_DIALOGUE_CATEGORY)
                ->whereUuid($this->category)
                ->getOrFail(__('student.dialogue_category.dialogue_category'), 'category') : null;

            $existingDialogue = Dialogue::whereHasMorph(
                'model', [Contact::class],
                function ($q) use ($student) {
                    $q->whereId($student->contact_id);
                }
            )
                ->when($dialogueUuid, function ($q, $dialogueUuid) {
                    $q->where('uuid', '!=', $dialogueUuid);
                })
                ->whereCategoryId($category?->id)
                ->whereTitle($this->title)
                ->exists();

            if ($existingDialogue) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('student.dialogue.props.title')]));
            }

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->where(function ($q) use ($dialogueUuid) {
                    $q->whereStatus(0)
                        ->when($dialogueUuid, function ($q) {
                            $q->orWhere('status', 1);
                        });
                })
                ->exists();

            if (! $attachedMedia) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
            }

            $this->merge([
                'category_id' => $category?->id,
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
            'title' => __('student.dialogue.props.title'),
            'description' => __('student.dialogue.props.description'),
            'start_date' => __('student.dialogue.props.start_date'),
            'end_date' => __('student.dialogue.props.end_date'),
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
