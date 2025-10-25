<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ProgramTypeRequest;
use App\Http\Resources\Academic\ProgramTypeResource;
use App\Services\Academic\ProgramTypeListService;
use App\Services\Academic\ProgramTypeService;
use Illuminate\Http\Request;

class ProgramTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:program:manage');
    }

    public function preRequisite(ProgramTypeService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, ProgramTypeListService $service)
    {
        return $service->paginate($request);
    }

    public function store(ProgramTypeRequest $request, ProgramTypeService $service)
    {
        $programType = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.program_type.program_type')]),
            'program_type' => ProgramTypeResource::make($programType),
        ]);
    }

    public function show(string $programType, ProgramTypeService $service): ProgramTypeResource
    {
        $programType = $service->findByUuidOrFail($programType);

        return ProgramTypeResource::make($programType);
    }

    public function update(ProgramTypeRequest $request, string $programType, ProgramTypeService $service)
    {
        $programType = $service->findByUuidOrFail($programType);

        $service->update($request, $programType);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.program_type.program_type')]),
        ]);
    }

    public function destroy(string $programType, ProgramTypeService $service)
    {
        $programType = $service->findByUuidOrFail($programType);

        $service->deletable($programType);

        $programType->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.program_type.program_type')]),
        ]);
    }
}
