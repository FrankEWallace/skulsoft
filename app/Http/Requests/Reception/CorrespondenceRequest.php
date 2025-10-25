<?php

namespace App\Http\Requests\Reception;

use App\Enums\Reception\CorrespondenceMode;
use App\Enums\Reception\CorrespondenceType;
use App\Models\Media;
use App\Models\Reception\Correspondence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class CorrespondenceRequest extends FormRequest
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
            'type' => ['required', new Enum(CorrespondenceType::class)],
            'mode' => ['required', new Enum(CorrespondenceMode::class)],
            'reference' => 'nullable',
            'sender_title' => 'required|min:2|max:255',
            'sender_address' => 'required|min:2|max:255',
            'receiver_title' => 'required|min:2|max:255',
            'receiver_address' => 'required|min:2|max:255',
            'letter_number' => 'required|min:2|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string|max:2000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Correspondence)->getModelName();

            $correspondenceUuid = $this->route('correspondence.uuid');

            $reference = $this->reference ? Correspondence::query()
                ->byTeam()
                ->whereUuid($this->reference)
                ->getOrFail(__('reception.correspondence.correspondence'), 'reference') : null;

            $existingCorrespondence = Correspondence::query()
                ->byTeam()
                ->when($correspondenceUuid, function ($q, $correspondenceUuid) {
                    $q->where('uuid', '!=', $correspondenceUuid);
                })
                ->whereLetterNumber($this->letter_number)
                ->exists();

            if ($existingCorrespondence) {
                $validator->errors()->add('letter_number', trans('validation.unique', ['attribute' => __('reception.correspondence.props.letter_number')]));
            }

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->where(function ($q) use ($correspondenceUuid) {
                    $q->whereStatus(0)
                        ->when($correspondenceUuid, function ($q) {
                            $q->orWhere('status', 1);
                        });
                })
                ->exists();

            if (! $attachedMedia) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
            }

            $this->merge([
                'reference_id' => $reference?->id,
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
            'type' => __('reception.correspondence.props.type'),
            'mode' => __('reception.correspondence.props.mode'),
            'sender_title' => __('reception.correspondence.props.sender_title'),
            'sender_address' => __('reception.correspondence.props.sender_address'),
            'receiver_title' => __('reception.correspondence.props.receiver_title'),
            'receiver_address' => __('reception.correspondence.props.receiver_address'),
            'letter_number' => __('reception.correspondence.props.letter_number'),
            'date' => __('reception.correspondence.props.date'),
            'remarks' => __('reception.correspondence.props.remarks'),
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
