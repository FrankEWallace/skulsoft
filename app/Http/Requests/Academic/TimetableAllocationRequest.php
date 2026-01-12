<?php

namespace App\Http\Requests\Academic;

use App\Enums\Day;
use App\Domain\Academic\Models\Subject;
use App\Models\Asset\Building\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TimetableAllocationRequest extends FormRequest
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
            'days' => 'array',
            'days.*.value' => 'required|distinct',
            'days.*.sessions' => 'array',
            'days.*.sessions.*.allotments' => 'array',
            'days.*.sessions.*.allotments.*.subject.uuid' => 'nullable',
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

            $days = Day::getKeys();

            $subjects = Subject::query()
                ->byPeriod()
                ->get();

            if (count($this->days) != 7) {
                throw ValidationException::withMessages(['message' => trans('academic.timetable.invalid_days')]);
            }

            $rooms = Room::query()
                ->withFloorAndBlock()
                ->notAHostel()
                ->get();

            $newDays = [];
            foreach ($this->days as $index => $day) {
                $dayName = Arr::get($day, 'value');

                if (! in_array($dayName, $days)) {
                    throw ValidationException::withMessages(['message' => trans('academic.timetable.invalid_day')]);
                }

                $sessions = Arr::get($day, 'sessions', []);

                $newSessions = [];
                foreach ($sessions as $sessionIndex => $session) {

                    $allotments = Arr::get($session, 'allotments', []);

                    $allotmentSubjects = collect($allotments)->pluck('subject.uuid')->all();

                    if (count($allotmentSubjects) != count(array_unique($allotmentSubjects))) {
                        throw ValidationException::withMessages(['message' => trans('academic.timetable.duplicate_allotment')]);
                    }

                    $newAllotments = [];
                    foreach ($allotments as $allotmentIndex => $allotment) {
                        if (count($allotments) == 0) {
                            $validator->errors()->add('days.'.$index.'.sessions.'.$sessionIndex.'.allotments.'.$allotmentIndex.'.subject', trans('validation.required', ['attribute' => trans('academic.timetable.allotment')]));
                        }

                        if (count($allotments) > 1 && ! Arr::get($allotment, 'subject.uuid')) {
                            $validator->errors()->add('days.'.$index.'.sessions.'.$sessionIndex.'.allotments.'.$allotmentIndex.'.subject', trans('validation.required', ['attribute' => trans('academic.subject.subject')]));
                        }

                        $subject = $subjects->where('uuid', Arr::get($allotment, 'subject.uuid'))->first();

                        $room = null;
                        if (Arr::get($allotment, 'room')) {
                            $room = $rooms->where('uuid', Arr::get($allotment, 'room'))->first();

                            if (! $room) {
                                $validator->errors()->add('days.'.$index.'.sessions.'.$sessionIndex.'.allotments.'.$allotmentIndex.'.room', trans('global.could_not_find', ['attribute' => trans('asset.building.room.room')]));
                            }
                        }

                        if (Arr::get($allotment, 'subject.uuid') && ! $subject) {
                            $validator->errors()->add('days.'.$index.'.sessions.'.$sessionIndex.'.allotments.'.$allotmentIndex.'.subject', trans('global.could_not_find', ['attribute' => trans('academic.subject.subject')]));
                        }

                        $newAllotments[] = [
                            ...$allotment,
                            'room_id' => $room?->id,
                        ];
                    }

                    $newSessions[] = [
                        ...$session,
                        'allotments' => $newAllotments,
                    ];
                }

                $newDays[] = [
                    ...$day,
                    'sessions' => $newSessions,
                ];
            }

            $this->merge(['days' => $newDays]);
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
            'days' => __('list.durations.day'),
            'days.*.value' => __('list.durations.day'),
            'days.*.sessions' => __('academic.class_timing.session'),
            'days.*.sessions.*.allotments' => __('academic.class_timing.allotment'),
            'days.*.sessions.*.allotments.*.subject.uuid' => __('academic.subject.subject'),
            'days.*.sessions.*.allotments.*.room' => __('asset.building.room.room'),
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
        ];
    }
}
