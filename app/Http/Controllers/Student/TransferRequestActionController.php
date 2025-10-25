<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\TransferRequestActionRequest;
use App\Models\Student\TransferRequest;
use App\Services\Student\TransferRequestActionService;
use Illuminate\Http\Request;

class TransferRequestActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:student:transfer-request-action');
    }

    public function preRequisite(Request $request, string $transferRequest, TransferRequestActionService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function action(TransferRequestActionRequest $request, string $transferRequest, TransferRequestActionService $service)
    {
        $transferRequest = TransferRequest::findByUuidOrFail($transferRequest);

        $service->action($request, $transferRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.transfer_request.transfer_request')]),
        ]);
    }
}
