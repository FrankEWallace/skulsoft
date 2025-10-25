<?php

namespace App\Services\Transport;

use App\Enums\Transport\Direction;
use App\Models\Employee\Employee;
use App\Models\Student\Student;
use App\Models\Transport\Route;
use App\Models\Transport\RoutePassenger;
use Illuminate\Http\Request;

class RouteActionService
{
    public function addStudent(Request $request, Route $route)
    {
        $students = Student::query()
            ->byPeriod()
            ->whereIn('uuid', $request->students)
            ->get();

        $otherRoutes = Route::query()
            ->byPeriod()
            ->where('id', '!=', $route->id)
            ->when($route->direction == Direction::ARRIVAL, function ($query) {
                $query->whereIn('direction', [Direction::ARRIVAL, Direction::ROUND_TRIP]);
            })
            ->when($route->direction == Direction::DEPARTURE, function ($query) {
                $query->whereIn('direction', [Direction::DEPARTURE, Direction::ROUND_TRIP]);
            })
            ->get();

        $existingStudents = RoutePassenger::query()
            ->whereIn('route_id', $otherRoutes->pluck('id'))
            ->whereModelType('Student')
            ->pluck('model_id')
            ->all();

        foreach ($students as $student) {
            if (! in_array($student->id, $existingStudents)) {
                RoutePassenger::firstOrCreate([
                    'model_type' => 'Student',
                    'model_id' => $student->id,
                    'route_id' => $route->id,
                    'stoppage_id' => $request->stoppage_id,
                ]);
            }
        }
    }

    public function addEmployee(Request $request, Route $route)
    {
        $employees = Employee::query()
            ->byTeam()
            ->whereIn('uuid', $request->employees)
            ->get();

        foreach ($employees as $employee) {
            $passenger = RoutePassenger::firstOrCreate([
                'model_type' => 'Employee',
                'model_id' => $employee->id,
                'route_id' => $route->id,
            ]);

            $passenger->stoppage_id = $request->stoppage_id;
            $passenger->setMeta([
                'title' => $request->title,
                'publish_contact_number' => $request->boolean('publish_contact_number'),
            ]);
            $passenger->save();
        }
    }

    public function removePassenger(Request $request, Route $transportRoute, string $uuid): void
    {
        \DB::beginTransaction();

        $transportRoutePassenger = RoutePassenger::query()
            ->whereRouteId($transportRoute->id)
            ->whereUuid($uuid)
            ->firstOrFail();

        $transportRoutePassenger->delete();

        \DB::commit();
    }
}
