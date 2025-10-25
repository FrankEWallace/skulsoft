<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CertificateTemplateRequest;
use App\Http\Resources\Academic\CertificateTemplateResource;
use App\Models\Academic\CertificateTemplate;
use App\Services\Academic\CertificateTemplateListService;
use App\Services\Academic\CertificateTemplateService;
use Illuminate\Http\Request;

class CertificateTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, CertificateTemplateService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CertificateTemplateListService $service)
    {
        $this->authorize('viewAny', CertificateTemplate::class);

        return $service->paginate($request);
    }

    public function store(CertificateTemplateRequest $request, CertificateTemplateService $service)
    {
        $this->authorize('create', CertificateTemplate::class);

        $certificateTemplate = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.certificate.template.template')]),
            'certificate_template' => CertificateTemplateResource::make($certificateTemplate),
        ]);
    }

    public function show(string $certificateTemplate, CertificateTemplateService $service)
    {
        $certificateTemplate = CertificateTemplate::findByUuidOrFail($certificateTemplate);

        $this->authorize('view', $certificateTemplate);

        return CertificateTemplateResource::make($certificateTemplate);
    }

    public function export(Request $request, string $certificateTemplate, CertificateTemplateService $service)
    {
        $certificateTemplate = CertificateTemplate::findByUuidOrFail($certificateTemplate);

        $this->authorize('view', $certificateTemplate);

        return $service->export($certificateTemplate);
    }

    public function update(CertificateTemplateRequest $request, string $certificateTemplate, CertificateTemplateService $service)
    {
        $certificateTemplate = CertificateTemplate::findByUuidOrFail($certificateTemplate);

        $this->authorize('update', $certificateTemplate);

        $service->update($request, $certificateTemplate);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.certificate.template.template')]),
        ]);
    }

    public function destroy(string $certificateTemplate, CertificateTemplateService $service)
    {
        $certificateTemplate = CertificateTemplate::findByUuidOrFail($certificateTemplate);

        $this->authorize('delete', $certificateTemplate);

        $service->deletable($certificateTemplate);

        $certificateTemplate->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.certificate.template.template')]),
        ]);
    }
}
