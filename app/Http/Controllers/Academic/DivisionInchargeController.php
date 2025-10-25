<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\DivisionInchargeRequest;
use App\Http\Resources\Academic\DivisionInchargeResource;
use App\Models\Incharge;
use App\Services\Academic\DivisionInchargeListService;
use App\Services\Academic\DivisionInchargeService;
use Illuminate\Http\Request;

class DivisionInchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, DivisionInchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, DivisionInchargeListService $service)
    {
        $this->authorize('viewAny', Incharge::class, 'division');

        return $service->paginate($request);
    }

    public function store(DivisionInchargeRequest $request, DivisionInchargeService $service)
    {
        $this->authorize('create', Incharge::class, 'division');

        $divisionIncharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.division_incharge.division_incharge')]),
            'division_incharge' => DivisionInchargeResource::make($divisionIncharge),
        ]);
    }

    public function show(string $divisionIncharge, DivisionInchargeService $service)
    {
        $divisionIncharge = Incharge::findByUuidOrFail($divisionIncharge);

        $this->authorize('view', $divisionIncharge, 'division');

        $divisionIncharge->load(['model', 'employee' => fn ($q) => $q->summary()]);

        return DivisionInchargeResource::make($divisionIncharge);
    }

    public function update(DivisionInchargeRequest $request, string $divisionIncharge, DivisionInchargeService $service)
    {
        $divisionIncharge = Incharge::findByUuidOrFail($divisionIncharge);

        $this->authorize('update', $divisionIncharge);

        $service->update($request, $divisionIncharge, 'division');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.division_incharge.division_incharge')]),
        ]);
    }

    public function destroy(string $divisionIncharge, DivisionInchargeService $service)
    {
        $divisionIncharge = Incharge::findByUuidOrFail($divisionIncharge);

        $this->authorize('delete', $divisionIncharge, 'division');

        $service->deletable($divisionIncharge);

        $divisionIncharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.division_incharge.division_incharge')]),
        ]);
    }
}
