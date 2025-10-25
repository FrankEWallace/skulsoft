<?php

namespace App\Services\Transport;

use App\Enums\Transport\Direction;
use App\Helpers\CalHelper;
use App\Http\Resources\Transport\StoppageResource;
use App\Http\Resources\Transport\Vehicle\VehicleResource;
use App\Models\Transport\Route;
use App\Models\Transport\RouteStoppage;
use App\Models\Transport\Stoppage;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RouteService
{
    public function preRequisite(): array
    {
        $stoppages = StoppageResource::collection(Stoppage::query()
            ->byPeriod()
            ->get());

        $vehicles = VehicleResource::collection(Vehicle::query()
            ->byTeam()
            ->get());

        $directions = Direction::getOptions();

        return compact('stoppages', 'vehicles', 'directions');
    }

    public function create(Request $request): Route
    {
        \DB::beginTransaction();

        $route = Route::forceCreate($this->formatParams($request));

        $this->updateStoppages($request, $route);

        \DB::commit();

        return $route;
    }

    private function formatParams(Request $request, ?Route $route = null): array
    {
        $formatted = [
            'name' => $request->name,
            'vehicle_id' => $request->vehicle_id,
            'max_capacity' => $request->max_capacity,
            'direction' => $request->direction,
            'arrival_starts_at' => $request->arrival_starts_at ? CalHelper::storeDateTime($request->arrival_starts_at)->toTimeString() : null,
            'departure_starts_at' => $request->departure_starts_at ? CalHelper::storeDateTime($request->departure_starts_at)->toTimeString() : null,
            'duration_to_destination' => $request->duration_to_destination,
            'description' => $request->description,
        ];

        if (! $route) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    private function updateStoppages(Request $request, Route $route): void
    {
        $stoppageIds = [];

        foreach ($request->stoppages as $index => $stoppage) {
            $stoppageIds[] = Arr::get($stoppage, 'id');
            $routeStoppage = RouteStoppage::firstOrCreate([
                'route_id' => $route->id,
                'stoppage_id' => Arr::get($stoppage, 'id'),
            ]);

            $routeStoppage->position = $index + 1;
            $routeStoppage->arrival_time = Arr::get($stoppage, 'arrival_time');
            $routeStoppage->save();
        }

        RouteStoppage::query()
            ->whereRouteId($route->id)
            ->whereNotIn('stoppage_id', $stoppageIds)
            ->delete();
    }

    public function update(Request $request, Route $route): void
    {
        \DB::beginTransaction();

        $route->forceFill($this->formatParams($request, $route))->save();

        $this->updateStoppages($request, $route);

        \DB::commit();
    }

    public function deletable(Route $route, $validate = false): ?bool
    {
        $passengerExists = \DB::table('transport_route_passengers')
            ->whereStoppageId($route->id)
            ->exists();

        if ($passengerExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.route.route'), 'dependency' => trans('transport.route.passengers')])]);
        }

        return true;
    }
}
