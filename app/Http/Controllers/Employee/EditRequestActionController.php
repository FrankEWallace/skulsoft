<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ContactEditRequest;
use App\Services\Employee\EditRequestActionService;
use Illuminate\Http\Request;

class EditRequestActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee:edit-request-action');
    }

    public function action(Request $request, string $editRequest, EditRequestActionService $service)
    {
        $editRequest = ContactEditRequest::findForEmployeeByUuidOrFail($editRequest);

        $service->action($request, $editRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.edit_request.edit_request')]),
        ]);
    }
}
