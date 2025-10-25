<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\EnquiryQualificationRequest;
use App\Http\Resources\Reception\EnquiryQualificationResource;
use App\Models\Reception\Enquiry;
use App\Services\Reception\EnquiryQualificationService;
use Illuminate\Http\Request;

class EnquiryQualificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $enquiry, EnquiryQualificationService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $enquiry, EnquiryQualificationService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        return $service->list($request, $enquiry);
    }

    public function store(EnquiryQualificationRequest $request, string $enquiry, EnquiryQualificationService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('update', $enquiry);

        $qualification = $service->create($request, $enquiry);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.qualification.qualification')]),
            'qualification' => EnquiryQualificationResource::make($qualification),
        ]);
    }

    public function show(string $enquiry, string $qualification, EnquiryQualificationService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $qualification = $service->findByUuidOrFail($enquiry, $qualification);

        $qualification->load('level', 'media');

        return EnquiryQualificationResource::make($qualification);
    }

    public function update(EnquiryQualificationRequest $request, string $enquiry, string $qualification, EnquiryQualificationService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('update', $enquiry);

        $qualification = $service->findByUuidOrFail($enquiry, $qualification);

        $service->update($request, $enquiry, $qualification);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.qualification.qualification')]),
        ]);
    }

    public function destroy(string $enquiry, string $qualification, EnquiryQualificationService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('update', $enquiry);

        $qualification = $service->findByUuidOrFail($enquiry, $qualification);

        $service->deletable($enquiry, $qualification);

        $qualification->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.qualification.qualification')]),
        ]);
    }

    public function downloadMedia(string $enquiry, string $qualification, string $uuid, EnquiryQualificationService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $qualification = $service->findByUuidOrFail($enquiry, $qualification);

        return $qualification->downloadMedia($uuid);
    }
}
