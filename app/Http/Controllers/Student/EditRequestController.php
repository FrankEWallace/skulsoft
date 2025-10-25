<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\EditRequestResource;
use App\Models\ContactEditRequest;
use App\Services\Student\EditRequestListService;
use App\Services\Student\EditRequestService;
use Illuminate\Http\Request;

class EditRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:student:edit-request-action');
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
        $editRequest = ContactEditRequest::findDetailForStudentByUuidOrFail($editRequest);

        $editRequest->load('media');

        return EditRequestResource::make($editRequest);
    }

    public function downloadMedia(string $editRequest, string $uuid, EditRequestService $service)
    {
        $editRequest = ContactEditRequest::findForStudentByUuidOrFail($editRequest);

        return $editRequest->downloadMedia($uuid);
    }
}
