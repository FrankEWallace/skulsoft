<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RegistrationDetailRequest;
use App\Http\Requests\Student\RegistrationRequest;
use App\Http\Requests\Student\RegistrationUpdateRequest;
use App\Http\Resources\GuardianResource;
use App\Http\Resources\Student\DocumentResource;
use App\Http\Resources\Student\QualificationResource;
use App\Http\Resources\Student\RegistrationResource;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Services\Student\RegistrationListService;
use App\Services\Student\RegistrationService;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function preRequisite(RegistrationService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, RegistrationListService $service)
    {
        $this->authorize('viewAny', Registration::class);

        return $service->paginate($request);
    }

    public function store(RegistrationRequest $request, RegistrationService $service)
    {
        $this->authorize('create', Registration::class);

        $registration = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.registration.registration')]),
            'registration' => RegistrationResource::make($registration),
        ]);
    }

    public function show(Request $request, string $registration, RegistrationService $service): RegistrationResource
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        $registration->load(['stage', 'enrollmentType', 'period', 'course.division', 'contact' => function ($q) {
            $q->withGuardian()->with('guardian');
        }, 'transactions' => function ($q) {
            $q->withPayment();
        }, 'contact.caste', 'contact.category', 'contact.religion',  'admission.batch', 'employee' => fn ($q) => $q->summary(), 'media']);

        $student = null;
        if ($registration->admission?->id) {
            $student = Student::query()
                ->select('students.uuid')
                ->where('admission_id', $registration->admission->id)
                ->first();
        }

        $request->merge([
            'student_uuid' => $student?->uuid,
            'has_custom_fields' => true,
        ]);

        if ($request->query('detail')) {
            $registration->load(['contact.guardians.contact', 'contact.qualifications.level', 'contact.qualifications.media', 'contact.documents.type', 'contact.documents.media']);
        }

        return RegistrationResource::make($registration);
    }

    public function showGuardians(Request $request, string $registration, RegistrationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        $contact = $registration->contact;
        $contact->load('guardians.contact');

        return GuardianResource::collection($contact->guardians);
    }

    public function showQualifications(Request $request, string $enquiry, RegistrationService $service)
    {
        $enquiry = Registration::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $contact = $enquiry->contact;
        $contact->load('qualifications.level', 'qualifications.media');

        return QualificationResource::collection($contact->qualifications);
    }

    public function showDocuments(Request $request, string $enquiry, RegistrationService $service)
    {
        $enquiry = Registration::findByUuidOrFail($enquiry);

        $this->authorize('view', $enquiry);

        $contact = $enquiry->contact;
        $contact->load('documents.type', 'documents.media');

        return DocumentResource::collection($contact->documents);
    }

    public function update(RegistrationUpdateRequest $request, string $registration, RegistrationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $service->update($request, $registration);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.registration.registration')]),
        ]);
    }

    public function updateDetail(RegistrationDetailRequest $request, string $registration, RegistrationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $service->updateDetail($request, $registration);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.registration.registration')]),
        ]);
    }

    public function destroy(string $registration, RegistrationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('delete', $registration);

        $service->deletable($registration);

        $service->delete($registration);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.registration.registration')]),
        ]);
    }

    public function destroyMultiple(Request $request, RegistrationService $service)
    {
        $this->authorize('delete');

        $count = $service->deleteMultiple($request);

        return response()->success([
            'message' => trans('global.multiple_deleted', ['count' => $count, 'attribute' => trans('student.registration.registration')]),
        ]);
    }

    public function downloadMedia(string $registration, string $uuid, RegistrationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        return $registration->downloadMedia($uuid);
    }

    public function export(Request $request, string $registration, RegistrationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $registration->load(['stage', 'enrollmentType', 'period', 'course.division', 'contact', 'contact.qualifications.level', 'contact.documents.type', 'contact.guardians.contact', 'admission.batch', 'employee' => fn ($q) => $q->summary()]);

        $request->merge([
            'has_custom_fields' => true,
        ]);

        $registration = json_decode(RegistrationResource::make($registration)->toJson(), true);

        return view()->first([config('config.print.custom_path').'student.registration', 'print.student.registration'], compact('registration'));
    }
}
