<?php

namespace App\Services\Academic;

use App\Enums\Academic\CertificateFor;
use App\Enums\Academic\CertificateType;
use App\Enums\CustomFieldType;
use App\Models\Academic\Certificate;
use App\Models\Academic\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CertificateTemplateService
{
    public function preRequisite(Request $request)
    {
        $types = CertificateType::getOptions();

        $for = CertificateFor::getOptions();

        $customFieldTypes = CustomFieldType::getOptions();

        return compact('types', 'for', 'customFieldTypes');
    }

    public function create(Request $request): CertificateTemplate
    {
        \DB::beginTransaction();

        $certificateTemplate = CertificateTemplate::forceCreate($this->formatParams($request));

        \DB::commit();

        return $certificateTemplate;
    }

    private function formatParams(Request $request, ?CertificateTemplate $certificateTemplate = null): array
    {
        $formatted = [
            'name' => $request->name,
            'type' => $request->type,
            'for' => $request->for,
            'content' => $request->content,
            'custom_fields' => $request->custom_fields,
        ];

        $config = $certificateTemplate?->config;

        $config['number_prefix'] = $request->number_prefix;
        $config['number_digit'] = $request->number_digit;
        $config['number_suffix'] = $request->number_suffix;

        $config['has_custom_template_file'] = $request->boolean('has_custom_template_file');
        $config['custom_template_file_name'] = $request->custom_template_file_name;
        $config['has_custom_header'] = $request->boolean('has_custom_header');

        $formatted['config'] = $config;

        if (! $certificateTemplate) {
            $formatted['team_id'] = auth()->user()->current_team_id;
        }

        return $formatted;
    }

    public function export(CertificateTemplate $certificateTemplate)
    {
        $content = $certificateTemplate->content;

        $hasCustomTemplateFile = $certificateTemplate->getConfig('has_custom_template_file');

        if ($hasCustomTemplateFile) {
            $customTemplateFileName = $certificateTemplate->getConfig('custom_template_file_name');

            if (view()->exists(config('config.print.custom_path').'academic.certificate.templates.'.$customTemplateFileName)) {
                $content = view(config('config.print.custom_path').'academic.certificate.templates.'.$customTemplateFileName)->render();
            }
        }

        $certificate = new Certificate;
        $certificate->content = $content;
        $certificate->template = $certificateTemplate;

        return view('print.academic.certificate.index', compact('certificate'));
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate): void
    {
        \DB::beginTransaction();

        $certificateTemplate->forceFill($this->formatParams($request, $certificateTemplate))->save();

        \DB::commit();
    }

    public function deletable(CertificateTemplate $certificateTemplate): bool
    {
        $certificateExists = Certificate::whereTemplateId($certificateTemplate->id)->exists();

        if ($certificateExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.certificate.template.template'), 'dependency' => trans('academic.certificate.certificate')])]);
        }

        // throw ValidationException::withMessages(['message' => 'test']);

        return true;
    }
}
