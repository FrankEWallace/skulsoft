<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Division;
use App\Services\Academic\DivisionActionService;
use Illuminate\Http\Request;

class DivisionActionController extends Controller
{
    public function updateConfig(Request $request, string $division, DivisionActionService $service)
    {
        $division = Division::findByUuidOrFail($division);

        $this->authorize('update', $division);

        $service->updateConfig($request, $division);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.division.division')]),
        ]);
    }

    public function reorder(Request $request, DivisionActionService $service)
    {
        $this->authorize('create', Division::class);

        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.division.division')]),
        ]);
    }

    public function updateCurrentPeriod(Request $request, string $division, DivisionActionService $service)
    {
        $division = Division::findByUuidOrFail($division);

        $this->authorize('update', $division);

        $service->updateCurrentPeriod($request, $division);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.division.division')]),
        ]);
    }
}
