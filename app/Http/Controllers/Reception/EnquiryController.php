<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\EnquiryDetailRequest;
use App\Http\Requests\Reception\EnquiryRequest;
use App\Http\Resources\GuardianResource;
use App\Http\Resources\Reception\EnquiryResource;
use App\Http\Resources\Student\QualificationResource;
use App\Models\Reception\Enquiry;
use App\Services\Reception\EnquiryListService;
use App\Services\Reception\EnquiryService;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, EnquiryService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, EnquiryListService $service)
    {
        $this->authorize('viewAny', Enquiry::class);

        return $service->paginate($request);
    }

    public function store(EnquiryRequest $request, EnquiryService $service)
    {
        $this->authorize('create', Enquiry::class);

        $enquiry = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('reception.enquiry.enquiry')]),
            'enquiry' => EnquiryResource::make($enquiry),
        ]);
    }

    public function show(Request $request, string $enquiry, EnquiryService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $enquiry->load(['period', 'stage', 'type', 'source', 'course', 'contact', 'contact.caste', 'contact.category', 'contact.religion', 'followUps.stage', 'media', 'employee' => fn ($q) => $q->summary()]);

        $request->merge([
            'has_custom_fields' => true,
        ]);

        if ($request->query('detail')) {
            $enquiry->load(['contact.guardians.contact', 'contact.qualifications.level', 'contact.qualifications.media']);
        }

        return EnquiryResource::make($enquiry);
    }

    public function showGuardians(Request $request, string $enquiry, EnquiryService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $contact = $enquiry->contact;
        $contact->load('guardians.contact');

        return GuardianResource::collection($contact->guardians);
    }

    public function showQualifications(Request $request, string $enquiry, EnquiryService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $contact = $enquiry->contact;
        $contact->load('qualifications.level', 'qualifications.media');

        return QualificationResource::collection($contact->qualifications);
    }

    public function update(EnquiryRequest $request, string $enquiry, EnquiryService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('update', $enquiry);

        $service->update($request, $enquiry);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }

    public function updateDetail(EnquiryDetailRequest $request, string $enquiry, EnquiryService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('update', $enquiry);

        $service->updateDetail($request, $enquiry);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }

    public function destroy(string $enquiry, EnquiryService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $this->authorize('delete', $enquiry);

        $service->deletable($enquiry);

        $enquiry->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }

    public function destroyMultiple(Request $request, EnquiryService $service)
    {
        $this->authorize('delete');

        $count = $service->deleteMultiple($request);

        return response()->success([
            'message' => trans('global.multiple_deleted', ['count' => $count, 'attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }

    public function downloadMedia(Enquiry $enquiry, string $uuid, EnquiryService $service)
    {
        $this->authorize('view', $enquiry);

        return $enquiry->downloadMedia($uuid);
    }

    public function export(Request $request, string $enquiry, EnquiryService $service)
    {
        $enquiry = Enquiry::findByUuidOrFail($enquiry);

        $enquiry->load(['period', 'stage', 'type', 'source', 'course', 'contact', 'contact.caste', 'contact.guardians.contact', 'contact.category', 'contact.religion', 'contact.qualifications.level', 'followUps', 'media', 'employee' => fn ($q) => $q->summary()]);

        $request->merge([
            'has_custom_fields' => true,
        ]);

        $enquiry = json_decode(EnquiryResource::make($enquiry)->toJson(), true);

        return view()->first([config('config.print.custom_path').'reception.enquiry', 'print.reception.enquiry'], compact('enquiry'));
    }
}
