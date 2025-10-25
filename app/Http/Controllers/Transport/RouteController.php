<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\RouteRequest;
use App\Http\Resources\Transport\RouteResource;
use App\Models\Transport\Route;
use App\Services\Transport\RouteListService;
use App\Services\Transport\RouteService;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function preRequisite(RouteService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, RouteListService $service)
    {
        $this->authorize('viewAny', Route::class);

        return $service->paginate($request);
    }

    public function store(RouteRequest $request, RouteService $service)
    {
        $this->authorize('create', Route::class);

        $route = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.route.route')]),
            'route' => RouteResource::make($route),
        ]);
    }

    public function show(Request $request, string $route, RouteService $service): RouteResource
    {
        $route = Route::findByUuidOrFail($route);

        $this->authorize('view', $route);

        $route->load('vehicle', 'routeStoppages.stoppage', 'routePassengers.model.contact', 'routePassengers.stoppage');

        $request->merge(['show_passengers' => true]);

        return RouteResource::make($route);
    }

    public function update(RouteRequest $request, string $route, RouteService $service)
    {
        $route = Route::findByUuidOrFail($route);

        $this->authorize('update', $route);

        $service->update($request, $route);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.route.route')]),
        ]);
    }

    public function destroy(string $route, RouteService $service)
    {
        $route = Route::findByUuidOrFail($route);

        $this->authorize('delete', $route);

        $service->deletable($route);

        $route->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.route.route')]),
        ]);
    }

    public function export(Request $request, string $route, RouteService $service)
    {
        $route = Route::findByUuidOrFail($route);

        $route->load('vehicle', 'routeStoppages.stoppage', 'routePassengers.model.contact', 'routePassengers.stoppage');

        $request->merge([
            'show_contact_number' => true,
            'show_passengers' => true,
        ]);

        $inclusions = explode(',', $request->query('inclusions'));

        $route = json_decode(RouteResource::make($route)->toJson(), true);

        return view()->first([config('config.print.custom_path').'transport.route', 'print.transport.route'], compact('route', 'inclusions'));
    }
}
