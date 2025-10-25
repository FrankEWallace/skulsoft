<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ProgramRequest;
use App\Http\Resources\Academic\ProgramResource;
use App\Models\Academic\Program;
use App\Services\Academic\ProgramListService;
use App\Services\Academic\ProgramService;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function preRequisite(ProgramService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, ProgramListService $service)
    {
        return $service->paginate($request);
    }

    public function store(ProgramRequest $request, ProgramService $service)
    {
        $program = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.program.program')]),
            'program' => ProgramResource::make($program),
        ]);
    }

    public function show(string $program, ProgramService $service): ProgramResource
    {
        $program = Program::findByUuidOrFail($program);

        $program->load('department', 'type');

        return ProgramResource::make($program);
    }

    public function update(ProgramRequest $request, string $program, ProgramService $service)
    {
        $program = Program::findByUuidOrFail($program);

        $service->update($request, $program);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.program.program')]),
        ]);
    }

    public function destroy(string $program, ProgramService $service)
    {
        $program = Program::findByUuidOrFail($program);

        $service->deletable($program);

        $program->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.program.program')]),
        ]);
    }
}
