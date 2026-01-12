<?php

namespace App\Services\Calendar;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\OptionType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Helpers\CalHelper;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Academic\SessionResource;
use App\Http\Resources\OptionResource;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Session;
use App\Models\Calendar\Event;
use App\Models\Incharge;
use App\Models\Option;
use App\Support\FormatCodeNumber;
use App\Support\HasAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class EventService
{
    use FormatCodeNumber, HasAudience;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.calendar.event_number_prefix');
        $numberSuffix = config('config.calendar.event_number_suffix');
        $digit = config('config.calendar.event_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Event::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::EVENT_TYPE->value)
            ->get());

        $studentAudienceTypes = StudentAudienceType::getOptions();

        $employeeAudienceTypes = EmployeeAudienceType::getOptions();

        $sessions = SessionResource::collection(Session::query()
            ->byTeam()
            ->get());

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->get());

        return compact('types', 'studentAudienceTypes', 'employeeAudienceTypes', 'sessions', 'periods');
    }

    public function create(Request $request): Event
    {
        \DB::beginTransaction();

        $event = Event::forceCreate($this->formatParams($request));

        $this->storeAudience($event, $request->all());

        $this->updateIncharge($event, $request->all());

        $event->addMedia($request);

        \DB::commit();

        return $event;
    }

    private function formatParams(Request $request, ?Event $event = null): array
    {
        $startTime = $request->start_time ? CalHelper::storeDateTime($request->start_date.' '.$request->start_time)?->toTimeString() : null;

        $endTime = $request->end_date && $request->end_time ? CalHelper::storeDateTime($request->end_date.' '.$request->end_time)?->toTimeString() : null;

        $formatted = [
            'type_id' => $request->type_id,
            'title' => $request->title,
            'venue' => $request->venue,
            'start_date' => $request->start_date,
            'start_time' => $startTime,
            'end_date' => $request->end_date ?: null,
            'end_time' => $endTime,
            'is_public' => $request->boolean('is_public'),
            'audience' => [
                'student_type' => $request->student_audience_type,
                'employee_type' => $request->employee_audience_type,
            ],
            'description' => clean($request->description),
        ];

        if (! $event) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        $meta = $event?->meta ?? [];

        if ($request->for_alumni) {
            $meta['for_alumni'] = true;
            $meta['periods'] = $request->periods;
            $meta['sessions'] = $request->sessions;
        } else {
            $meta['for_alumni'] = false;
            $meta['periods'] = [];
            $meta['sessions'] = [];
        }

        $formatted['meta'] = $meta;

        return $formatted;
    }

    private function updateIncharge(Event $event, array $data): void
    {
        Incharge::firstOrCreate([
            'model_type' => 'Event',
            'model_id' => $event->id,
            'employee_id' => Arr::get($data, 'incharge_id'),
            'start_date' => $event->start_date->value,
        ]);

        Incharge::where('model_type', 'Event')
            ->where('model_id', $event->id)
            ->where('employee_id', '!=', Arr::get($data, 'incharge_id'))
            ->delete();
    }

    public function update(Request $request, Event $event): void
    {
        \DB::beginTransaction();

        $this->prepareAudienceForUpdate($event, $request->all());

        $event->forceFill($this->formatParams($request, $event))->save();

        $this->updateAudience($event, $request->all());

        $this->updateIncharge($event, $request->all());

        $event->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Event $event): void
    {
        //
    }
}
