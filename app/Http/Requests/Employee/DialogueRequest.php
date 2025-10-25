<?php

namespace App\Http\Requests\Employee;

use App\Enums\OptionType;
use App\Models\Contact;
use App\Models\Dialogue;
use App\Models\Employee\Employee;
use App\Models\Media;
use App\Models\Option;
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
            'description' => 'nullable|min:2|max:500',
            'date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $employeeUuid = $this->route('employee');
            $dialogueUuid = $this->route('dialogue');

            $employee = Employee::query()
                ->byTeam()
                ->whereUuid($employeeUuid)
                ->firstOrFail();

            $mediaModel = (new Dialogue)->getModelName();

            $category = $this->category ? Option::query()
                ->byTeam()
                ->whereType(OptionType::EMPLOYEE_DIALOGUE_CATEGORY)
                ->whereUuid($this->category)
                ->getOrFail(__('employee.dialogue_category.dialogue_category'), 'category') : null;

            $existingDialogue = Dialogue::whereHasMorph(
                'model', [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
                ->when($dialogueUuid, function ($q, $dialogueUuid) {
                    $q->where('uuid', '!=', $dialogueUuid);
                })
                ->whereCategoryId($category?->id)
                ->whereTitle($this->title)
                ->exists();

            if ($existingDialogue) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('employee.dialogue.props.title')]));
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
            'category' => __('employee.dialogue_category.dialogue_category'),
            'title' => __('employee.dialogue.props.title'),
            'description' => __('employee.dialogue.props.description'),
            'date' => __('employee.dialogue.props.date'),
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
