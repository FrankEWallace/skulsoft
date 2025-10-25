<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\DivisionRequest;
use App\Http\Resources\Academic\DivisionResource;
use App\Models\Academic\Division;
use App\Services\Academic\DivisionListService;
use App\Services\Academic\DivisionService;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function preRequisite(DivisionService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, DivisionListService $service)
    {
        $this->authorize('viewAny', Division::class);

        return $service->paginate($request);
    }

    public function store(DivisionRequest $request, DivisionService $service)
    {
        $this->authorize('create', Division::class);

        $division = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.division.division')]),
            'division' => DivisionResource::make($division),
        ]);
    }

    public function show(Request $request, string $division, DivisionService $service): DivisionResource
    {
        $division = Division::findByUuidOrFail($division);

        $division->load('courses', 'program');

        $this->authorize('view', $division);

        $request->merge([
            'details' => true,
        ]);

        return DivisionResource::make($division);
    }

    public function update(DivisionRequest $request, string $division, DivisionService $service)
    {
        $division = Division::findByUuidOrFail($division);

        $this->authorize('update', $division);

        $service->update($request, $division);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.division.division')]),
        ]);
    }

    public function destroy(string $division, DivisionService $service)
    {
        $division = Division::findByUuidOrFail($division);

        $this->authorize('delete', $division);

        $service->deletable($division);

        $division->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.division.division')]),
        ]);
    }
}
