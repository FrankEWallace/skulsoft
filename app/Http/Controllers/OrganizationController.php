<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationListService;
use App\Services\OrganizationService;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:organization:manage');
    }

    public function index(Request $request, OrganizationListService $service)
    {
        return $service->paginate($request);
    }

    public function store(OrganizationRequest $request, OrganizationService $service)
    {
        $organization = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('organization.organization')]),
            'organization' => OrganizationResource::make($organization),
        ]);
    }

    public function show(Organization $organization, OrganizationService $service): OrganizationResource
    {
        return OrganizationResource::make($organization);
    }

    public function update(OrganizationRequest $request, Organization $organization, OrganizationService $service)
    {
        $service->update($request, $organization);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('organization.organization')]),
        ]);
    }

    public function destroy(Organization $organization, OrganizationService $service)
    {
        $service->deletable($organization);

        $service->delete($organization);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('organization.organization')]),
        ]);
    }
}
