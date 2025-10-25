<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\TransferRequestRequest;
use App\Http\Resources\Student\TransferRequestResource;
use App\Models\Student\TransferRequest;
use App\Services\Student\TransferRequestListService;
use App\Services\Student\TransferRequestService;
use Illuminate\Http\Request;

class TransferRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:student:transfer-request');
    }

    public function preRequisite(Request $request, TransferRequestService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, TransferRequestListService $service)
    {
        return $service->paginate($request);
    }

    public function store(TransferRequestRequest $request, TransferRequestService $service)
    {
        $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.transfer_request.transfer_request')]),
        ]);
    }

    public function show(string $transferRequest, TransferRequestService $service)
    {
        $transferRequest = TransferRequest::findByUuidOrFail($transferRequest);

        $transferRequest->load('media');

        return TransferRequestResource::make($transferRequest);
    }

    public function update(TransferRequestRequest $request, string $transferRequest, TransferRequestService $service)
    {
        $transferRequest = TransferRequest::findByUuidOrFail($transferRequest);

        $service->update($request, $transferRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.transfer_request.transfer_request')]),
        ]);
    }

    public function destroy(Request $request, string $transferRequest, TransferRequestService $service)
    {
        $transferRequest = TransferRequest::findByUuidOrFail($transferRequest);

        $service->deletable($request, $transferRequest);

        $service->delete($transferRequest);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.transfer_request.transfer_request')]),
        ]);
    }

    public function downloadMedia(string $transferRequest, string $uuid, TransferRequestService $service)
    {
        $transferRequest = TransferRequest::findByUuidOrFail($transferRequest);

        return $transferRequest->downloadMedia($uuid);
    }
}
