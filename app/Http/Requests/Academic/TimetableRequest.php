<?php

namespace App\Http\Requests\Academic;

use App\Enums\Day;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassTiming;
use App\Models\Academic\Timetable;
use App\Models\Asset\Building\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class TimetableRequest extends FormRequest
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
        $rules = [
            'batch' => 'required|uuid',
            'effective_date' => 'required|date_format:Y-m-d',
            'records' => 'array',
            'room' => 'nullable|uuid',
            'records.*.is_holiday' => 'boolean',
            'records.*.class_timing' => 'required_if:records.*.is_holiday,false|nullable|uuid',
            'description' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('timetable');

            $batch = Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->where('uuid', $this->batch)
                ->getOrFail(trans('academic.batch.batch'), 'batch');

            $classTimings = ClassTiming::query()
                ->byPeriod()
                ->get();

            $room = $this->room ? Room::query()
                ->withFloorAndBlock()
                ->notAHostel()
                ->where('rooms.uuid', $this->room)
                ->getOrFail(trans('asset.building.room.room')) : null;

            $existingRecords = Timetable::query()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereBatchId($batch->id)
                ->whereEffectiveDate($this->effective_date)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.timetable.timetable')]));
            }

            $days = Day::getKeys();
            $daysInput = [];
            $records = [];

            foreach ($this->records as $index => $record) {
                $daysInput[] = Arr::get($record, 'day');
                $isHoliday = (bool) Arr::get($record, 'is_holiday');
                $classTiming = Arr::get($record, 'class_timing');

                if (! $isHoliday && $classTimings->where('uuid', $classTiming)->isEmpty()) {
                    $validator->errors()->add("records.{$index}.class_timing", __('global.could_not_find', ['attribute' => trans('academic.class_timing.class_timing')]));
                }

                $records[] = [
                    'day' => Arr::get($record, 'day'),
                    'is_holiday' => $isHoliday,
                    'class_timing_id' => ! $isHoliday ? $classTimings->where('uuid', $classTiming)->first()?->id : null,
                ];
            }

            if (array_diff($days, $daysInput)) {
                $validator->errors()->add('records', __('academic.timetable.all_days_should_be_filled'));
            }

            $this->merge([
                'batch_id' => $batch->id,
                'room_id' => $room?->id,
                'records' => $records,
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
            'batch' => __('academic.batch.batch'),
            'effective_date' => __('academic.timetable.props.effective_date'),
            'room' => __('asset.building.room.room'),
            'records' => __('academic.timetable.timetable'),
            'records.*.day' => __('academic.timetable.props.day'),
            'records.*.is_holiday' => __('academic.timetable.props.holiday'),
            'records.*.class_timing' => __('academic.class_timing.class_timing'),
            'description' => __('academic.timetable.props.description'),
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
            'records.*.class_timing.required_if' => __('validation.required', ['attribute' => __('academic.class_timing.class_timing')]),
        ];
    }
}
