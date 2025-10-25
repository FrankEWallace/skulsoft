<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ServiceRequestRequest;
use App\Http\Resources\Student\ServiceRequestResource;
use App\Models\Student\ServiceRequest;
use App\Services\Student\ServiceRequestListService;
use App\Services\Student\ServiceRequestService;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:student:service-request');
    }

    public function preRequisite(Request $request, ServiceRequestService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, ServiceRequestListService $service)
    {
        return $service->paginate($request);
    }

    public function store(ServiceRequestRequest $request, ServiceRequestService $service)
    {
        $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.service_request.service_request')]),
        ]);
    }

    public function show(string $serviceRequest, ServiceRequestService $service)
    {
        $serviceRequest = ServiceRequest::findDetailByUuidOrFail($serviceRequest);

        $serviceRequest->load('media', 'requestRecords');

        return ServiceRequestResource::make($serviceRequest);
    }

    public function update(ServiceRequestRequest $request, string $serviceRequest, ServiceRequestService $service)
    {
        $serviceRequest = ServiceRequest::findByUuidOrFail($serviceRequest);

        $service->update($request, $serviceRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.service_request.service_request')]),
        ]);
    }

    public function destroy(Request $request, string $serviceRequest, ServiceRequestService $service)
    {
        $serviceRequest = ServiceRequest::findByUuidOrFail($serviceRequest);

        $service->deletable($request, $serviceRequest);

        $service->delete($serviceRequest);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.service_request.service_request')]),
        ]);
    }

    public function downloadMedia(string $serviceRequest, string $uuid, ServiceRequestService $service)
    {
        $serviceRequest = ServiceRequest::findByUuidOrFail($serviceRequest);

        return $serviceRequest->downloadMedia($uuid);
    }
}
