<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\PeriodRequest;
use App\Http\Resources\Academic\PeriodResource;
use App\Models\Academic\Period;
use App\Services\Academic\PeriodListService;
use App\Services\Academic\PeriodService;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    public function preRequisite(PeriodService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, PeriodListService $service)
    {
        $this->authorize('viewAny', Period::class);

        return $service->paginate($request);
    }

    public function store(PeriodRequest $request, PeriodService $service)
    {
        $this->authorize('create', Period::class);

        $period = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.period.period')]),
            'period' => PeriodResource::make($period),
        ]);
    }

    public function show(Request $request, string $period, PeriodService $service): PeriodResource
    {
        $period = $service->findByUuidOrFail($period);

        $this->authorize('view', $period);

        $period->load('session');

        return PeriodResource::make($period);
    }

    public function update(PeriodRequest $request, string $period, PeriodService $service)
    {
        $period = $service->findByUuidOrFail($period);

        $this->authorize('update', $period);

        $service->update($request, $period);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.period.period')]),
        ]);
    }

    public function destroy(string $period, PeriodService $service)
    {
        $period = $service->findByUuidOrFail($period);

        $this->authorize('delete', $period);

        $service->deletable($period);

        $period->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.period.period')]),
        ]);
    }
}
