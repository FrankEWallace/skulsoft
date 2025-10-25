<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\TypeRequest;
use App\Http\Resources\Approval\TypeResource;
use App\Models\Approval\Type;
use App\Services\Approval\TypeListService;
use App\Services\Approval\TypeService;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:approval-type:manage')->only(['store', 'update', 'destroy']);
    }

    public function preRequisite(Request $request, TypeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, TypeListService $service)
    {
        return $service->paginate($request);
    }

    public function store(TypeRequest $request, TypeService $service)
    {
        $approvalType = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('approval.type.type')]),
            'approval_type' => TypeResource::make($approvalType),
        ]);
    }

    public function show(string $approvalType, TypeService $service)
    {
        $approvalType = Type::findByUuidOrFail($approvalType);

        $date = today()->toDateString();

        $approvalType->load(['department', 'levels' => fn ($q) => $q->orderBy('position', 'asc'), 'levels.employee' => fn ($q) => $q->summary($date, true)]);

        return TypeResource::make($approvalType);
    }

    public function update(TypeRequest $request, string $approvalType, TypeService $service)
    {
        $approvalType = Type::findByUuidOrFail($approvalType);

        $service->update($request, $approvalType);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('approval.type.type')]),
        ]);
    }

    public function destroy(Request $request, string $approvalType, TypeService $service)
    {
        $approvalType = Type::findByUuidOrFail($approvalType);

        $service->deletable($request, $approvalType);

        $approvalType->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('approval.type.type')]),
        ]);
    }

    public function downloadMedia(string $approvalType, string $uuid, TypeService $service)
    {
        $approvalType = Type::findByUuidOrFail($approvalType);

        return $approvalType->downloadMedia($uuid);
    }
}
