<?php

namespace App\Http\Requests\Resource;

use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Media;
use App\Models\Resource\LearningMaterial;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class LearningMaterialRequest extends FormRequest
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
            'batches' => 'array|min:1',
            'subject' => 'nullable|uuid',
            'description' => 'nullable|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new LearningMaterial)->getModelName();

            $learningMaterialUuid = $this->route('learning_material');

            $batches = Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereIn('uuid', $this->batches)
                ->listOrFail(trans('academic.batch.batch'), 'batches');

            $subject = null;
            if ($this->subject) {
                foreach ($batches as $batch) {
                    $subject = Subject::query()
                        ->findByBatchOrFail($batch->id, $batch->course_id, $this->subject);
                }
            }

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->where(function ($q) use ($learningMaterialUuid) {
                    $q->whereStatus(0)
                        ->when($learningMaterialUuid, function ($q) {
                            $q->orWhere('status', 1);
                        });
                })
                ->exists();

            if (! $attachedMedia) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
            }

            $this->merge([
                'batch_ids' => $batches->pluck('id')->all(),
                'subject_id' => $subject?->id,
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
            'title' => __('resource.learning_material.props.title'),
            'batches' => __('academic.batch.batch'),
            'subject' => __('academic.subject.subject'),
            'description' => __('resource.learning_material.props.description'),
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
