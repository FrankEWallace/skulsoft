<?php

namespace App\Services\Academic;

use App\Concerns\CertificateTemplateParser;
use App\Http\Resources\Academic\CertificateTemplateSummaryResource;
use App\Domain\Academic\Models\Certificate;
use App\Domain\Academic\Models\CertificateTemplate;
use App\Support\FormatCodeNumber;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CertificateService
{
    use CertificateTemplateParser, FormatCodeNumber;

    private function codeNumber(CertificateTemplate $template): array
    {
        $numberPrefix = $template->getConfig('number_prefix');
        $numberSuffix = $template->getConfig('number_suffix');
        $digit = $template->getConfig('number_digit') ?? 3;

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Certificate::query()
            ->byTeam()
            ->where('template_id', $template->id)
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request)
    {
        $templates = CertificateTemplateSummaryResource::collection(CertificateTemplate::query()
            ->byTeam()
            ->get());

        return compact('templates');
    }

    public function create(Request $request): Certificate
    {
        \DB::beginTransaction();

        $certificate = Certificate::forceCreate($this->formatParams($request));

        \DB::commit();

        return $certificate;
    }

    private function formatParams(Request $request, ?Certificate $certificate = null): array
    {
        $formatted = [
            'template_id' => $request->template->id,
            'model_type' => $request->model_type,
            'model_id' => $request->model?->id,
            'date' => $request->date,
            'custom_fields' => $request->custom_fields,
        ];

        if (! $certificate) {
            $codeNumberDetail = $this->codeNumber($request->template);

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
        }

        $meta = $certificate?->meta ?? [];
        if ($request->boolean('is_duplicate')) {
            $meta['is_duplicate'] = true;
        }

        if (empty($modelType)) {
            $meta['name'] = $request->name;
        }

        $formatted['meta'] = $meta;

        // $content = $this->parse($request->template, $request->model, $request->custom_fields, [
        //     'certificate_number' => $certificate?->code_number ?? $formatted['code_number'],
        //     'certificate_date' => $certificate?->date?->value ?? $formatted['date'],
        // ]);

        // $formatted['content'] = $content;

        return $formatted;
    }

    private function getContent(Certificate $certificate): string
    {
        $qrCode = (new QRCode)->render(
            $certificate->code_number
        );

        if (empty($certificate->model_type)) {
            return $this->parseEmptyModel($certificate->template, $certificate->custom_fields, [
                'name' => $certificate->getMeta('name'),
                'certificate_number' => $certificate->code_number,
                'certificate_date' => $certificate->date->value,
                'qr_code' => $qrCode,
            ]);
        }

        return $this->parse($certificate->template, $certificate->model, $certificate->custom_fields, [
            'certificate_number' => $certificate->code_number,
            'certificate_date' => $certificate->date->value,
            'qr_code' => $qrCode,
        ]);
    }

    public function export(Certificate $certificate)
    {
        $certificate->content = $this->getContent($certificate);

        return view('print.academic.certificate.index', compact('certificate'));
    }

    public function getCertificateContent(Certificate $certificate)
    {
        $certificate->content = $this->getContent($certificate);

        return $certificate;
    }

    public function update(Request $request, Certificate $certificate): void
    {
        \DB::beginTransaction();

        $certificate->forceFill($this->formatParams($request, $certificate))->save();

        \DB::commit();
    }

    public function deletable(Certificate $certificate): bool
    {
        // throw ValidationException::withMessages(['message' => 'test']);

        return true;
    }
}
