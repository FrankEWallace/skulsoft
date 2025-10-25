<?php

namespace App\Services\Dashboard;

use App\Enums\Transport\Direction;
use App\Models\Student\Student;
use App\Models\Transport\Route;
use App\Models\Transport\RoutePassenger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TransportRouteService
{
    public function fetch(Request $request)
    {
        $students = Student::query()
            ->byPeriod()
            ->record()
            ->filterForStudentAndGuardian()
            ->get();

        $transportRoutes = Route::query()
            ->byPeriod()
            ->with('vehicle', 'routeStoppages.stoppage')
            ->whereHas('routePassengers', function ($q) use ($students) {
                $q->whereModelType('Student')
                    ->whereIn('model_id', $students->pluck('id')->all());
            })
            ->get();

        $routePassengers = RoutePassenger::query()
            ->whereIn('route_id', $transportRoutes->pluck('id')->all())
            ->whereModelType('Employee')
            ->with('model.contact')
            ->get();

        $routes = [];

        foreach ($transportRoutes as $transportRoute) {
            $arrivalStoppages = [];
            $departureStoppages = [];
            if ($transportRoute->direction == Direction::ARRIVAL || $transportRoute->direction == Direction::ROUND_TRIP) {
                $arrivalStoppages = $transportRoute->getArrivalStoppageTimings();
            }

            if ($transportRoute->direction == Direction::DEPARTURE || $transportRoute->direction == Direction::ROUND_TRIP) {
                $departureStoppages = $transportRoute->getDepartureStoppageTimings();
            }

            $employees = $routePassengers
                ->where('route_id', $transportRoute->id)
                ->filter(function ($routePassenger) {
                    return ! empty($routePassenger->getMeta('title'));
                })
                ->map(function ($routePassenger) {
                    return [
                        'name' => $routePassenger->model?->contact->name,
                        'title' => $routePassenger->getMeta('title'),
                        'contact_number' => $routePassenger->getMeta('publish_contact_number') ? $routePassenger->model->contact->contact_number : '',
                    ];
                })
                ->values();

            $routes[] = [
                'vehicle' => [
                    'name' => $transportRoute->vehicle->name,
                    'registration_number' => Arr::get($transportRoute->vehicle->registration, 'number'),
                ],
                'arrival_starts_at' => $transportRoute->arrival_starts_at,
                'arrival_stoppages' => $arrivalStoppages,
                'departure_starts_at' => $transportRoute->departure_starts_at,
                'departure_stoppages' => $departureStoppages,
                'employees' => $employees,
            ];
        }

        return $routes;
    }
}
