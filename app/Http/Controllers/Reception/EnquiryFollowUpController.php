<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\EnquiryFollowUpRequest;
use App\Http\Resources\Reception\EnquiryFollowUpResource;
use App\Models\Reception\Enquiry;
use App\Services\Reception\EnquiryFollowUpService;
use App\Services\Reception\EnquiryListService;
use Illuminate\Http\Request;

class EnquiryFollowUpController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $enquiry, EnquiryFollowUpService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        return $service->preRequisite($request);
    }

    public function index(Request $request, string $enquiry, EnquiryListService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        return $service->paginate($request);
    }

    public function store(EnquiryFollowUpRequest $request, string $enquiry, EnquiryFollowUpService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('followUp', $enquiry);

        $followUp = $service->create($request, $enquiry);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('reception.enquiry.follow_up.follow_up')]),
            'follow_up' => EnquiryFollowUpResource::make($followUp),
        ]);
    }

    public function show(string $enquiry, string $followUp, EnquiryFollowUpService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $followUp = $service->findByUuidOrFail($enquiry, $followUp);

        $enquiry->load('user');

        return EnquiryFollowUpResource::make($enquiry);
    }

    public function update(EnquiryFollowUpRequest $request, string $enquiry, string $followUp, EnquiryFollowUpService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('followUp', $enquiry);

        $followUp = $service->findByUuidOrFail($enquiry, $followUp);

        $service->update($request, $enquiry, $followUp);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.enquiry.follow_up.follow_up')]),
        ]);
    }

    public function destroy(string $enquiry, string $followUp, EnquiryFollowUpService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('followUp', $enquiry);

        $followUp = $service->findByUuidOrFail($enquiry, $followUp);

        $service->deletable($enquiry, $followUp);

        $followUp->delete();

        $service->updateEnquiryStatus($enquiry);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.enquiry.follow_up.follow_up')]),
        ]);
    }
}
