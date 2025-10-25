<?php

namespace App\Services\Dashboard;

use App\Models\Mess\MealLog;
use Illuminate\Http\Request;

class MessScheduleService
{
    public function fetch(Request $request)
    {
        $mealLogs = MealLog::query()
            ->with('meal', 'records.item')
            ->whereHas('meal', function ($q) {
                $q->byTeam();
            })
            ->whereBetween('date', [today()->toDateString(), today()->addWeek(1)->toDateString()])
            ->get();

        $mealLogs = $mealLogs->groupBy('date.value');

        $schedules = [];
        foreach ($mealLogs as $date => $logs) {
            $meals = $logs->map(function ($log) {
                $items = $log->records->map(function ($record) {
                    return $record->item->name;
                })->implode(', ');

                if ($log->description) {
                    $items .= ' ('.$log->description.')';
                }

                return [
                    'name' => $log->meal->name,
                    'position' => $log->meal->position,
                    'items' => $items,
                ];
            });

            $schedules[] = [
                'date' => \Cal::date($date),
                'meals' => collect($meals)->sortBy('position')->values(),
            ];
        }

        return $schedules;
    }
}
