<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ServiceRequestActionRequest;
use App\Models\Student\ServiceRequest;
use App\Services\Student\ServiceRequestActionService;

class ServiceRequestActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:student:service-request-action');
    }

    public function updateStatus(ServiceRequestActionRequest $request, string $uuid, ServiceRequestActionService $service)
    {
        $serviceRequest = ServiceRequest::findByUuidOrFail($uuid);

        $service->updateStatus($request, $serviceRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.service_request.props.status')]),
        ]);
    }
}
