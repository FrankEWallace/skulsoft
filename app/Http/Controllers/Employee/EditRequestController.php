<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\EditRequestResource;
use App\Models\ContactEditRequest;
use App\Services\Employee\EditRequestListService;
use App\Services\Employee\EditRequestService;
use Illuminate\Http\Request;

class EditRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee:edit-request-action');
    }

    public function preRequisite(Request $request, EditRequestService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, EditRequestListService $service)
    {
        return $service->paginate($request);
    }

    public function show(string $editRequest, EditRequestService $service)
    {
        $editRequest = ContactEditRequest::findDetailForEmployeeByUuidOrFail($editRequest);

        $editRequest->load('media');

        return EditRequestResource::make($editRequest);
    }

    public function downloadMedia(string $editRequest, string $uuid, EditRequestService $service)
    {
        $editRequest = ContactEditRequest::findForEmployeeByUuidOrFail($editRequest);

        return $editRequest->downloadMedia($uuid);
    }
}
