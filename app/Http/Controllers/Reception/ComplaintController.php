<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\ComplaintRequest;
use App\Http\Resources\Reception\ComplaintResource;
use App\Models\Reception\Complaint;
use App\Services\Reception\ComplaintListService;
use App\Services\Reception\ComplaintService;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, ComplaintService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, ComplaintListService $service)
    {
        $this->authorize('viewAny', Complaint::class);

        return $service->paginate($request);
    }

    public function store(ComplaintRequest $request, ComplaintService $service)
    {
        $this->authorize('create', Complaint::class);

        $complaint = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('reception.complaint.complaint')]),
            'complaint' => ComplaintResource::make($complaint),
        ]);
    }

    public function show(string $complaint, ComplaintService $service)
    {
        $complaint = Complaint::findDetailByUuidOrFail($complaint);

        $this->authorize('view', $complaint);

        $complaint->load(['logs.user', 'media', 'incharges', 'incharges.employee' => fn ($q) => $q->summary()]);

        return ComplaintResource::make($complaint);
    }

    public function update(ComplaintRequest $request, string $complaint, ComplaintService $service)
    {
        $complaint = Complaint::findByUuidOrFail($complaint);

        $this->authorize('update', $complaint);

        $service->update($request, $complaint);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.complaint.complaint')]),
        ]);
    }

    public function destroy(Request $request, string $complaint, ComplaintService $service)
    {
        $complaint = Complaint::findByUuidOrFail($complaint);

        $this->authorize('delete', $complaint);

        $service->deletable($request, $complaint);

        $complaint->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.complaint.complaint')]),
        ]);
    }

    public function downloadMedia(string $complaint, string $uuid, ComplaintService $service)
    {
        $complaint = Complaint::findByUuidOrFail($complaint);

        $this->authorize('view', $complaint);

        return $complaint->downloadMedia($uuid);
    }
}
