<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\DesignationRequest;
use App\Http\Resources\Employee\DesignationResource;
use App\Models\Employee\Designation;
use App\Services\Employee\DesignationListService;
use App\Services\Employee\DesignationService;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, DesignationService $service)
    {
        $this->authorize('preRequisite', Designation::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, DesignationListService $service)
    {
        $this->authorize('viewAny', Designation::class);

        return $service->paginate($request);
    }

    public function store(DesignationRequest $request, DesignationService $service)
    {
        $this->authorize('create', Designation::class);

        $designation = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.designation.designation')]),
            'designation' => DesignationResource::make($designation),
        ]);
    }

    public function show(Designation $designation, DesignationService $service)
    {
        $this->authorize('view', $designation);

        $designation->load('parent');

        return DesignationResource::make($designation);
    }

    public function update(DesignationRequest $request, Designation $designation, DesignationService $service)
    {
        $this->authorize('update', $designation);

        $service->update($request, $designation);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.designation.designation')]),
        ]);
    }

    public function destroy(Designation $designation, DesignationService $service)
    {
        $this->authorize('delete', $designation);

        $service->deletable($designation);

        $designation->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.designation.designation')]),
        ]);
    }
}
