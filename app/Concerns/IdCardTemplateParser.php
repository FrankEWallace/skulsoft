<?php

namespace App\Concerns;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Models\Employee\Employee;
use App\Models\Employee\Record;
use App\Models\Guardian;
use App\Models\Student\Student;
use App\Support\NumberToWordConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait IdCardTemplateParser
{
    public function parse(string $content, Model $model, array $params = []): string
    {
        if ($model instanceof Student) {
            $content = $this->parseStudentVariable($content, $model);
        } elseif ($model instanceof Guardian) {
            $content = $this->parseGuardianVariable($content, $model);
        } elseif ($model instanceof Employee) {
            $content = $this->parseEmployeeVariable($content, $model);
        }

        $content = str_replace('#INSTITUTE_NAME#', config('config.team.name'), $content);
        $content = str_replace('#INSTITUTE_ADDRESS#', Arr::toAddress([
            'address_line1' => config('config.team.config.address_line1'),
            'address_line2' => config('config.team.config.address_line2'),
            'city' => config('config.team.config.city'),
            'state' => config('config.team.config.state'),
            'country' => config('config.team.config.country'),
            'zipcode' => config('config.team.config.zipcode'),
        ]), $content);
        $content = str_replace('#INSTITUTE_LOGO#', url(config('config.assets.logo')), $content);
        $content = str_replace('#SIGNATURE#', url(config('config.assets.signature', 'https://placehold.co/100x50')), $content);

        $contact = $model->contact;
        $content = str_replace('#PERIOD#', config('config.academic.period.name'), $content);
        $content = str_replace('#CURRENT_DATE#', \Cal::date(today()->toDateString())->formatted, $content);

        $content = str_replace('#NAME#', $contact->name, $content);
        $content = str_replace('#DOB#', $contact->birth_date->formatted, $content);
        $content = str_replace('#DOB_IN_WORDS#', NumberToWordConverter::dateToWord($contact->birth_date->value), $content);
        $content = str_replace('#GENDER#', Gender::getDetail($contact->gender)['label'], $content);
        $content = str_replace('#FATHER_NAME#', $contact->father_name, $content);
        $content = str_replace('#MOTHER_NAME#', $contact->mother_name, $content);
        $content = str_replace('#NATIONALITY#', $contact->nationality, $content);
        $content = str_replace('#CATEGORY#', $contact->category?->name, $content);
        $content = str_replace('#CASTE#', $contact->caste?->name, $content);
        $content = str_replace('#BLOOD_GROUP#', BloodGroup::getDetail($contact->blood_group)['label'] ?? 'N/A', $content);
        $content = str_replace('#RELIGION#', $contact->religion?->name, $content);
        $content = str_replace('#ADDRESS#', Arr::toAddress($contact->present_address), $content);
        $content = str_replace('#CONTACT_NUMBER#', $contact->contact_number, $content);

        $content = str_replace('#PHOTO#', $contact->photo_url, $content);

        $content = str_replace('#QRCODE#', Arr::get($params, 'qr_code'), $content);

        return $content;
    }

    private function parseStudentVariable(string $content, Model $student): string
    {
        $admission = $student->admission;

        $content = str_replace('#ADMISSION_NUMBER#', $admission->code_number, $content);
        $content = str_replace('#ADMISSION_DATE#', $admission->joining_date->formatted, $content);
        $content = str_replace('#TRANSFER_DATE#', $admission->leaving_date->formatted, $content);
        $content = str_replace('#COURSE_BATCH#', $student->batch->course->name.' '.$student->batch->name, $content);
        $content = str_replace('#ROLL_NUMBER#', $student->roll_number, $content);
        $content = str_replace('#ROUTE_NAME#', $student->route_name, $content);
        $content = str_replace('#STOPPAGE_NAME#', $student->stoppage_name, $content);

        return $content;
    }

    private function parseGuardianVariable(string $content, Model $guardian): string
    {
        $contact = $guardian->contact;

        $relatedStudents = $guardian->related_students;
        $studentNames = $relatedStudents->pluck('name')->implode(', ');
        $studentCodeNumbers = $relatedStudents->pluck('code_number')->implode(', ');
        $studentBatches = $relatedStudents->map(function ($student) {
            return $student->batch->course->name.' '.$student->batch->name;
        })->implode(', ');

        $content = str_replace('#STUDENT_NAMES#', $studentNames, $content);
        $content = str_replace('#STUDENT_ADMISSION_NUMBERS#', $studentCodeNumbers, $content);
        $content = str_replace('#STUDENT_COURSE_BATCHES#', $studentBatches, $content);

        return $content;
    }

    private function parseEmployeeVariable(string $content, Model $employee): string
    {
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
