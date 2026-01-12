<?php

namespace App\Concerns;

use App\Enums\Academic\CertificateFor;
use App\Enums\CustomFieldType;
use App\Enums\Gender;
use App\Domain\Academic\Models\CertificateTemplate;
use App\Domain\Academic\Models\Period;
use App\Models\Employee\Record;
use App\Support\NumberToWordConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait CertificateTemplateParser
{
    public function parse(CertificateTemplate $template, Model $model, array $customFields = [], array $params = []): string
    {
        $content = $template->content;

        $hasCustomTemplateFile = $template->getConfig('has_custom_template_file');

        if ($hasCustomTemplateFile) {
            $customTemplateFileName = $template->getConfig('custom_template_file_name');

            if (view()->exists(config('config.print.custom_path').'academic.certificate.templates.'.$customTemplateFileName)) {
                $content = view(config('config.print.custom_path').'academic.certificate.templates.'.$customTemplateFileName)->render();
            }
        }

        $content = $this->parseStudentVariable($template, $model, $content);

        $content = $this->parseEmployeeVariable($template, $model, $content);

        $periodName = config('config.academic.period.name');

        if ($template->for == CertificateFor::STUDENT) {
            $periodName = Period::query()
                ->find($model->period_id)
                ?->name;
        } elseif ($template->for == CertificateFor::EMPLOYEE) {
            $periodName = Period::query()
                ->find(auth()->user()->current_period_id)
                ?->name;
        }

        $contact = $model->contact;
        $content = str_replace('#CERTIFICATE_NUMBER#', Arr::get($params, 'certificate_number'), $content);
        $content = str_replace('#CERTIFICATE_DATE#', \Cal::date(Arr::get($params, 'certificate_date'))?->formatted, $content);
        $content = str_replace('#PERIOD#', $periodName, $content);
        $content = str_replace('#CURRENT_DATE#', \Cal::date(today()->toDateString())->formatted, $content);

        $content = str_replace('#NAME#', $contact->name, $content);
        $content = str_replace('#DOB#', $contact->birth_date->formatted, $content);
        $content = str_replace('#PHOTO', $contact->photo_url, $content);
        $content = str_replace('#DOB_IN_WORDS#', NumberToWordConverter::dateToWord($contact->birth_date->value), $content);
        $content = str_replace('#GENDER#', Gender::getDetail($contact->gender)['label'], $content);
        $content = str_replace('#FATHER_NAME#', $contact->father_name, $content);
        $content = str_replace('#MOTHER_NAME#', $contact->mother_name, $content);
        $content = str_replace('#NATIONALITY#', $contact->nationality, $content);
        $content = str_replace('#CATEGORY#', $contact->category?->name, $content);
        $content = str_replace('#CASTE#', $contact->caste?->name, $content);
        $content = str_replace('#RELIGION#', $contact->religion?->name, $content);
        $content = str_replace('#ADDRESS#', Arr::toAddress($contact->present_address), $content);

        $content = str_replace('#QRCODE#', Arr::get($params, 'qr_code'), $content);

        $variables = collect($template->custom_fields);

        foreach ($customFields as $name => $value) {
            $fieldName = '#'.$name.'#';

            $templateVariable = $variables->firstWhere('name', $name) ?? [];

            if (Arr::get($templateVariable, 'type') == CustomFieldType::DATE_PICKER->value) {
                $value = \Cal::date($value)->formatted;
            }

            $content = str_replace($fieldName, $value, $content);
        }

        return $content;
    }

    public function parseEmptyModel(CertificateTemplate $template, array $customFields = [], array $params = []): string
    {
        $content = $template->content;

        $hasCustomTemplateFile = $template->getConfig('has_custom_template_file');

        if ($hasCustomTemplateFile) {
            $customTemplateFileName = $template->getConfig('custom_template_file_name');

            if (view()->exists(config('config.print.custom_path').'academic.certificate.templates.'.$customTemplateFileName)) {
                $content = view(config('config.print.custom_path').'academic.certificate.templates.'.$customTemplateFileName)->render();
            }
        }

        $periodName = Period::query()
            ->find(auth()->user()->current_period_id)
            ?->name;

        $content = str_replace('#CERTIFICATE_NUMBER#', Arr::get($params, 'certificate_number'), $content);
        $content = str_replace('#CERTIFICATE_DATE#', \Cal::date(Arr::get($params, 'certificate_date'))?->formatted, $content);
        $content = str_replace('#PERIOD#', $periodName, $content);
        $content = str_replace('#CURRENT_DATE#', \Cal::date(today()->toDateString())->formatted, $content);

        $content = str_replace('#NAME#', Arr::get($params, 'name'), $content);

        $content = str_replace('#QRCODE#', Arr::get($params, 'qr_code'), $content);

        $variables = collect($template->custom_fields);

        foreach ($customFields as $name => $value) {
            $fieldName = '#'.$name.'#';

            $templateVariable = $variables->firstWhere('name', $name) ?? [];

            if (Arr::get($templateVariable, 'type') == CustomFieldType::DATE_PICKER->value) {
                $value = \Cal::date($value)->formatted;
            }

            $content = str_replace($fieldName, $value, $content);
        }

        return $content;
    }

    private function parseStudentVariable(CertificateTemplate $template, Model $student, string $content): string
    {
        if ($template->for != CertificateFor::STUDENT) {
            return $content;
        }

        $admission = $student->admission;

        $content = str_replace('#ADMISSION_NUMBER#', $admission->code_number, $content);
        $content = str_replace('#ADMISSION_DATE#', $admission->joining_date->formatted, $content);
        $content = str_replace('#TRANSFER_DATE#', $admission->leaving_date->formatted, $content);
        $content = str_replace('#COURSE_BATCH#', $student->batch->course->name.' '.$student->batch->name, $content);
        $content = str_replace('#ROLL_NUMBER#', $student->roll_number, $content);

        $concessionSummary = $student->getFeeConcessionSummary();

        $content = str_replace('#FEE_CONCESSION_AVAILED#', $concessionSummary, $content);

        $feeSummary = $student->getFeeSummary();

        $content = str_replace('#FEE_TOTAL#', Arr::get($feeSummary, 'total_fee', 0)?->formatted, $content);
        $content = str_replace('#FEE_PAID#', Arr::get($feeSummary, 'paid_fee', 0)?->formatted, $content);
        $content = str_replace('#FEE_BALANCE#', Arr::get($feeSummary, 'balance_fee', 0)?->formatted, $content);

        $subjectSummary = $student->getSubjectSummary();

        $content = str_replace('#SUBJECT_STUDYING#', $subjectSummary, $content);

        $attendanceSummary = $student->getAttendanceSummary();

        $content = str_replace('#PRESENT_DAYS#', Arr::get($attendanceSummary, 'present_days'), $content);
        $content = str_replace('#ABSENT_DAYS#', Arr::get($attendanceSummary, 'absent_days'), $content);
        $content = str_replace('#WORKING_DAYS#', Arr::get($attendanceSummary, 'working_days'), $content);

        return $content;
    }

    private function parseEmployeeVariable(CertificateTemplate $template, Model $employee, string $content): string
    {
        if ($template->for != CertificateFor::EMPLOYEE) {
            return $content;
        }

        $employeeRecord = Record::query()
            ->with('designation', 'department')
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', today()->toDateString())
            ->orderBy('start_date', 'desc')
            ->first();

        $content = str_replace('#EMPLOYEE_CODE#', $employee->code_number, $content);
        $content = str_replace('#JOINING_DATE#', $employee->joining_date->formatted, $content);
        $content = str_replace('#LEAVING_DATE#', $employee->leaving_date->formatted, $content);
        $content = str_replace('#DESIGNATION#', $employeeRecord?->designation?->name, $content);
        $content = str_replace('#DEPARTMENT#', $employeeRecord?->department?->name, $content);

        return $content;
    }
}
