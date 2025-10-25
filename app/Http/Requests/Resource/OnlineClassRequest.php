<?php

namespace App\Http\Requests\Resource;

use App\Enums\Resource\OnlineClassPlatform;
use App\Helpers\CalHelper;
use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Media;
use App\Models\Resource\OnlineClass;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class OnlineClassRequest extends FormRequest
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
        $rules = [
            'topic' => 'required|max:255',
            'batches' => 'array|min:1',
            'subject' => 'nullable|uuid',
            'start_at' => 'required|date_format:Y-m-d H:i:s',
            'duration' => 'required|integer|min:1',
            'platform' => ['required', new Enum(OnlineClassPlatform::class)],
            'password' => 'nullable|max:100',
            'description' => 'nullable|max:10000',
        ];

        if (config('config.resource.online_class_use_meeting_code')) {
            $rules['meeting_code'] = 'required|max:200';
        } else {
            $rules['url'] = 'required|url|max:255';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new OnlineClass)->getModelName();

            $onlineClassUuid = $this->route('online_class');

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
                ->where(function ($q) use ($onlineClassUuid) {
                    $q->whereStatus(0)
                        ->when($onlineClassUuid, function ($q) {
                            $q->orWhere('status', 1);
                        });
                })
                ->exists();

            // if (! $attachedMedia) {
            //     throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
            // }

            if (CalHelper::storeDateTime($this->start_at)->toDateTimeString() < now()->toDateTimeString()) {
                throw ValidationException::withMessages(['start_at' => trans('resource.online_class.start_at_lt_current_time')]);
            }

            $existingRecord = OnlineClass::query()
                ->where('topic', $this->topic)
                ->where('start_at', CalHelper::storeDateTime($this->start_at)->toDateTimeString())
                ->where('duration', $this->duration)
                ->when($onlineClassUuid, function ($q) use ($onlineClassUuid) {
                    $q->where('uuid', '!=', $onlineClassUuid);
                })
                ->exists();

            if ($existingRecord) {
                throw ValidationException::withMessages(['start_at' => trans('global.duplicate', ['attribute' => trans('resource.online_class.online_class')])]);
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
            'topic' => __('resource.online_class.props.topic'),
            'batches' => __('academic.batch.batch'),
            'subject' => __('academic.subject.subject'),
            'start_at' => __('resource.online_class.props.start_at'),
            'duration' => __('resource.online_class.props.duration'),
            'platform' => __('resource.online_class.props.platform'),
            'url' => __('resource.online_class.props.url'),
            'description' => __('resource.online_class.props.description'),
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
