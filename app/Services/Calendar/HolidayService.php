<?php

namespace App\Services\Calendar;

use App\Enums\Day;
use App\Domain\Academic\Models\Period;
use App\Models\Calendar\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HolidayService
{
    public function preRequisite(Request $request): array
    {
        $days = Day::getOptions();

        return compact('days');
    }

    public function create(Request $request): Holiday
    {
        $holiday = new Holiday;
        \DB::beginTransaction();

        if ($request->type == 'range') {
            $holiday = Holiday::forceCreate($this->formatParams($request));
        } elseif ($request->type == 'dates') {
            foreach ($request->dates as $date) {
                $request->merge([
                    'start_date' => $date,
                    'end_date' => $date,
                ]);
                $holiday = Holiday::forceCreate($this->formatParams($request));
            }
        } elseif ($request->type == 'weekend') {
            $period = Period::query()
                ->findOrFail(auth()->user()->current_period_id);

            $startDate = Carbon::parse($period->start_date->value);
            $endDate = Carbon::parse($period->end_date->value);

            $existingHolidays = Holiday::query()
                ->where('period_id', auth()->user()->current_period_id)
                ->get();

            $holidays = [];
            while ($startDate->lte($endDate)) {
                if (in_array(strtolower($startDate->format('l')), $request->days)) {
                    if (! $existingHolidays->where('start_date.value', '<=', $startDate->format('Y-m-d'))->where('end_date.value', '>=', $startDate->format('Y-m-d'))->count()) {
                        $holidays[] = [
                            'uuid' => (string) Str::uuid(),
                            'period_id' => auth()->user()->current_period_id,
                            'name' => $request->name,
                            'start_date' => $startDate->format('Y-m-d'),
                            'end_date' => $startDate->format('Y-m-d'),
                            'description' => $request->description,
                            'created_at' => now()->toDateTimeString(),
                        ];
                    }
                }

                $startDate->addDay();
            }

            if (count($holidays)) {
                Holiday::insert($holidays);
            }
        }

        \DB::commit();

        return $holiday;
    }

    private function formatParams(Request $request, ?Holiday $holiday = null): array
    {
        $formatted = [
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ];

        if (! $holiday) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Holiday $holiday): void
    {
        \DB::beginTransaction();

        $holiday->forceFill($this->formatParams($request, $holiday))->save();

        \DB::commit();
    }

    public function deletable(Holiday $holiday): void {}
}
