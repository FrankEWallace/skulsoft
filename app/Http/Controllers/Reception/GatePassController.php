<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\GatePassRequest;
use App\Http\Resources\Reception\GatePassResource;
use App\Models\Reception\GatePass;
use App\Services\Reception\GatePassListService;
use App\Services\Reception\GatePassService;
use Illuminate\Http\Request;

class GatePassController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, GatePassService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, GatePassListService $service)
    {
        $this->authorize('viewAny', GatePass::class);

        return $service->paginate($request);
    }

    public function store(GatePassRequest $request, GatePassService $service)
    {
        $this->authorize('create', GatePass::class);

        $gatePass = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('reception.gate_pass.gate_pass')]),
            'gate_pass' => GatePassResource::make($gatePass),
        ]);
    }

    public function show(GatePass $gatePass, GatePassService $service)
    {
        $this->authorize('view', $gatePass);

        $gatePass->load('purpose', 'audiences.audienceable.contact', 'media');

        return GatePassResource::make($gatePass);
    }

    public function update(GatePassRequest $request, GatePass $gatePass, GatePassService $service)
    {
        $this->authorize('update', $gatePass);

        $service->update($request, $gatePass);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.gate_pass.gate_pass')]),
        ]);
    }

    public function destroy(GatePass $gatePass, GatePassService $service)
    {
        $this->authorize('delete', $gatePass);

        $service->deletable($gatePass);

        $gatePass->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.gate_pass.gate_pass')]),
        ]);
    }

    public function downloadMedia(GatePass $gatePass, string $uuid, GatePassService $service)
    {
        $this->authorize('view', $gatePass);

        return $gatePass->downloadMedia($uuid);
    }

    public function export(GatePass $gatePass, GatePassService $service)
    {
        $this->authorize('view', $gatePass);

        $gatePass->load('purpose', 'audiences.audienceable.contact');

        $gatePass = json_decode(GatePassResource::make($gatePass)->toJson(), true);

        return view()->first([config('config.print.custom_path').'reception.gate-pass', 'print.reception.gate-pass'], compact('gatePass'));
    }
}
