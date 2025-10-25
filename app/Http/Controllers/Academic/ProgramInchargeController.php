<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ProgramInchargeRequest;
use App\Http\Resources\Academic\ProgramInchargeResource;
use App\Models\Incharge;
use App\Services\Academic\ProgramInchargeListService;
use App\Services\Academic\ProgramInchargeService;
use Illuminate\Http\Request;

class ProgramInchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:program:manage');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, ProgramInchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, ProgramInchargeListService $service)
    {
        $this->authorize('viewAny', Incharge::class, 'program');

        return $service->paginate($request);
    }

    public function store(ProgramInchargeRequest $request, ProgramInchargeService $service)
    {
        $programIncharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.program_incharge.program_incharge')]),
            'program_incharge' => ProgramInchargeResource::make($programIncharge),
        ]);
    }

    public function show(string $programIncharge, ProgramInchargeService $service)
    {
        $programIncharge = Incharge::findByUuidOrFail($programIncharge);

        $programIncharge->load(['model', 'employee' => fn ($q) => $q->summary()]);

        return ProgramInchargeResource::make($programIncharge);
    }

    public function update(ProgramInchargeRequest $request, string $programIncharge, ProgramInchargeService $service)
    {
        $programIncharge = Incharge::findByUuidOrFail($programIncharge);

        $service->update($request, $programIncharge, 'program');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.program_incharge.program_incharge')]),
        ]);
    }

    public function destroy(string $programIncharge, ProgramInchargeService $service)
    {
        $programIncharge = Incharge::findByUuidOrFail($programIncharge);

        $service->deletable($programIncharge);

        $programIncharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.program_incharge.program_incharge')]),
        ]);
    }
}
