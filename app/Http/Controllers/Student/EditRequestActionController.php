<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ContactEditRequest;
use App\Services\Student\EditRequestActionService;
use Illuminate\Http\Request;

class EditRequestActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:student:edit-request-action');
    }

    public function action(Request $request, string $editRequest, EditRequestActionService $service)
    {
        $editRequest = ContactEditRequest::findForStudentByUuidOrFail($editRequest);

        $service->action($request, $editRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.edit_request.edit_request')]),
        ]);
    }
}
