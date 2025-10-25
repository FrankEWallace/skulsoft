<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CertificateRequest;
use App\Http\Resources\Academic\CertificateResource;
use App\Models\Academic\Certificate;
use App\Services\Academic\CertificateListService;
use App\Services\Academic\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, CertificateService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CertificateListService $service)
    {
        $this->authorize('viewAny', Certificate::class);

        return $service->paginate($request);
    }

    public function store(CertificateRequest $request, CertificateService $service)
    {
        $this->authorize('create', Certificate::class);

        $certificate = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.certificate.certificate')]),
            'certificate' => CertificateResource::make($certificate),
        ]);
    }

    public function show(string $certificate, CertificateService $service)
    {
        $certificate = Certificate::findByUuidOrFail($certificate);

        $this->authorize('view', $certificate);

        $certificate->load('template', 'model.contact');

        return CertificateResource::make($certificate);
    }

    public function export(Request $request, string $certificate, CertificateService $service)
    {
        $certificate = Certificate::findByUuidOrFail($certificate);

        $this->authorize('view', $certificate);

        return $service->export($certificate);
    }

    public function exportAll(Request $request, CertificateListService $listService, CertificateService $service)
    {
        $this->authorize('viewAny', Certificate::class);

        $data = $listService->paginate($request);

        $certificates = json_decode($data->toJson(), true);

        $data = [];
        foreach ($certificates as $certificate) {
            $certificate = Certificate::findByUuidOrFail($certificate['uuid']);
            $certificate = $service->getCertificateContent($certificate);

            $data[] = view('print.academic.certificate.export', compact('certificate'))->render();
        }

        return view('print.academic.certificate.export-all', compact('data'));
    }

    public function update(CertificateRequest $request, string $certificate, CertificateService $service)
    {
        $certificate = Certificate::findByUuidOrFail($certificate);

        $this->authorize('update', $certificate);

        $service->update($request, $certificate);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.certificate.certificate')]),
        ]);
    }

    public function destroy(string $certificate, CertificateService $service)
    {
        $certificate = Certificate::findByUuidOrFail($certificate);

        $this->authorize('delete', $certificate);

        $service->deletable($certificate);

        $certificate->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.certificate.certificate')]),
        ]);
    }
}
