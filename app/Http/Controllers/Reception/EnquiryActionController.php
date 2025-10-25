<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Reception\Enquiry;
use App\Services\Contact\PhotoService;
use App\Services\Reception\EnquiryActionService;
use Illuminate\Http\Request;

class EnquiryActionController extends Controller
{
    public function uploadPhoto(Request $request, string $enquiry, EnquiryActionService $service, PhotoService $photoService)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $photoService->upload($request, $enquiry->contact);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function removePhoto(Request $request, string $enquiry, EnquiryActionService $service, PhotoService $photoService)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $photoService->remove($request, $enquiry->contact);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function convertToRegistration(Request $request, string $enquiry, EnquiryActionService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('action', $enquiry);

        $service->convertToRegistration($request, $enquiry);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.registration.registration')]),
        ]);
    }

    public function bulkConvertToRegistration(Request $request, EnquiryActionService $service)
    {
        $this->authorize('bulkAction', Enquiry::class);

        $convertedCount = $service->bulkConvertToRegistration($request);

        return response()->success([
            'message' => trans('reception.enquiry.successfully_converted_to_registration', ['attribute' => $convertedCount]),
        ]);
    }

    public function updateBulkAssignTo(Request $request, EnquiryActionService $service)
    {
        $this->authorize('bulkUpdate', Enquiry::class);

        $service->updateBulkAssignTo($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }

    public function updateBulkStage(Request $request, EnquiryActionService $service)
    {
        $this->authorize('bulkUpdate', Enquiry::class);

        $service->updateBulkStage($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }

    public function updateBulkType(Request $request, EnquiryActionService $service)
    {
        $this->authorize('bulkUpdate', Enquiry::class);

        $service->updateBulkType($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }

    public function updateBulkSource(Request $request, EnquiryActionService $service)
    {
        $this->authorize('bulkUpdate', Enquiry::class);

        $service->updateBulkSource($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }
}
