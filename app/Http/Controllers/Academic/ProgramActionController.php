<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Domain\Academic\Models\Program;
use App\Services\Academic\ProgramActionService;
use Illuminate\Http\Request;

class ProgramActionController extends Controller
{
    public function updateConfig(Request $request, string $program, ProgramActionService $service)
    {
        $program = Program::findByUuidOrFail($program);

        $service->updateConfig($request, $program);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.program.program')]),
        ]);
    }

    public function reorder(Request $request, ProgramActionService $service)
    {
        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.program.program')]),
        ]);
    }
}
