<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ProfileEditRequestRequest;
use App\Http\Resources\Student\ProfileEditRequestResource;
use App\Models\Student\Student;
use App\Services\Student\ProfileEditRequestListService;
use App\Services\Student\ProfileEditRequestService;
use Illuminate\Http\Request;

class ProfileEditRequestController extends Controller
{
    public function __construct() {}

    public function index(Request $request, string $student, ProfileEditRequestListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(ProfileEditRequestRequest $request, string $student, ProfileEditRequestService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('editRequest', $student);

        $service->create($request, $student);

        return response()->success([
            'message' => trans('student.edit_request.submitted'),
        ]);
    }

    public function show(string $student, string $editRequest, ProfileEditRequestService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $editRequest = $service->findByUuidOrFail($student, $editRequest);

        $editRequest->load('user', 'media');

        return ProfileEditRequestResource::make($editRequest);
    }

    public function downloadMedia(string $student, string $editRequest, string $uuid, ProfileEditRequestService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $editRequest = $service->findByUuidOrFail($student, $editRequest);

        return $editRequest->downloadMedia($uuid);
    }
}
