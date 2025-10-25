<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Contact\PhotoService;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction');
    }

    public function upload(Request $request, string $student, PhotoService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $service->upload($request, $student->contact);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function remove(Request $request, string $student, PhotoService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $service->remove($request, $student->contact);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('contact.props.photo')]),
        ]);
    }
}
