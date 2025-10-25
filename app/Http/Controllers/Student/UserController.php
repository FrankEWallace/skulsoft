<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\UserRequest;
use App\Http\Requests\Contact\UserUpdateRequest;
use App\Models\Student\Student;
use App\Services\Contact\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        // $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function confirm(Request $request, string $student, UserService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->confirm($request, $student->contact);
    }

    public function index(string $student, UserService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->fetch($student->contact));
    }

    public function create(UserRequest $request, string $student, UserService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $service->create($request, $student->contact);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.login.login')]),
        ]);
    }

    public function update(UserUpdateRequest $request, string $student, UserService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $student->load('contact.user');

        $this->denyAdmin($student?->contact?->user);

        $service->update($request, $student->contact);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.login.login')]),
        ]);
    }

    public function updateCurrentPeriod(Request $request, string $student, UserService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $student->load('contact.user');

        $this->denyAdmin($student?->contact?->user);

        $service->updateCurrentPeriod($request, $student?->contact?->user);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.period.current_period')]),
        ]);
    }
}
