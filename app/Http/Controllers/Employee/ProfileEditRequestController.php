<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ProfileEditRequestRequest;
use App\Http\Resources\Employee\ProfileEditRequestResource;
use App\Models\Employee\Employee;
use App\Services\Employee\ProfileEditRequestListService;
use App\Services\Employee\ProfileEditRequestService;
use Illuminate\Http\Request;

class ProfileEditRequestController extends Controller
{
    public function __construct() {}

    public function index(Request $request, string $employee, ProfileEditRequestListService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->paginate($request, $employee);
    }

    public function store(ProfileEditRequestRequest $request, string $employee, ProfileEditRequestService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('editRequest', $employee);

        $service->create($request, $employee);

        return response()->success([
            'message' => trans('employee.edit_request.submitted'),
        ]);
    }

    public function show(string $employee, string $editRequest, ProfileEditRequestService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $editRequest = $service->findByUuidOrFail($employee, $editRequest);

        $editRequest->load('user', 'media');

        return ProfileEditRequestResource::make($editRequest);
    }

    public function downloadMedia(string $employee, string $editRequest, string $uuid, ProfileEditRequestService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $editRequest = $service->findByUuidOrFail($employee, $editRequest);

        return $editRequest->downloadMedia($uuid);
    }
}
