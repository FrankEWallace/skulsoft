<?php

namespace App\Imports\Reception;

use App\Actions\CreateContact;
use App\Concerns\ItemImport;
use App\Enums\ContactSource;
use App\Enums\Gender;
use App\Enums\OptionType;
use App\Enums\Reception\EnquiryNature;
use App\Enums\Reception\EnquiryStatus;
use App\Helpers\CalHelper;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Reception\Enquiry;
use App\Support\FormatCodeNumber;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class EnquiryImport implements ToCollection, WithHeadingRow
{
    use FormatCodeNumber, ItemImport;

    protected $limit = 500;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('enquiry');

        [$errors, $rows] = $this->validate($rows);

        $this->checkForErrors('enquiry', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function codeNumber(): array
    {
        $numberPrefix = config('config.reception.enquiry_number_prefix');
        $numberSuffix = config('config.reception.enquiry_number_suffix');
        $digit = config('config.reception.enquiry_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Enquiry::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        foreach ($rows as $row) {
            $codeNumberDetail = $this->codeNumber();

            $enquiryDate = Arr::get($row, 'enquiry_date');
            $birthDate = Arr::get($row, 'birth_date');

            if (is_int($enquiryDate)) {
                $enquiryDate = Date::excelToDateTimeObject($enquiryDate)->format('Y-m-d');
            } else {
                $enquiryDate = Carbon::parse($enquiryDate)->toDateString();
            }

            if (is_int($birthDate)) {
                $birthDate = Date::excelToDateTimeObject($birthDate)->format('Y-m-d');
            } else {
                $birthDate = Carbon::parse($birthDate)->toDateString();
            }

            $contact = (new CreateContact)->execute([
                'name' => Arr::get($row, 'student_name'),
                'father_name' => Arr::get($row, 'father_name'),
                'email' => Arr::get($row, 'email'),
                'contact_number' => (string) Arr::get($row, 'contact_number'),
                'gender' => strtolower(Arr::get($row, 'gender')),
                'birth_date' => $birthDate,
                'validate' => false,
            ]);

            $address = $contact->address ?? [];
            $address['present']['address_line1'] = Arr::get($row, 'address');
            $contact->address = $address;
            $contact->category_id = Arr::get($row, 'category_id');
            $contact->setMeta([
                'source' => ContactSource::ENQUIRY->value,
                'previous_qualification' => Arr::get($row, 'previous_qualification'),
                'previous_institute_name' => Arr::get($row, 'previous_institute_name'),
                'previous_institute_affiliated_to' => Arr::get($row, 'previous_institute_affiliated_to'),
            ]);
            $contact->save();

            $enquiry = Enquiry::forceCreate([
                'number_format' => Arr::get($codeNumberDetail, 'number_format'),
                'number' => Arr::get($codeNumberDetail, 'number'),
                'code_number' => Arr::get($codeNumberDetail, 'code_number'),
                'nature' => EnquiryNature::ADMISSION->value,
                'period_id' => auth()->user()?->current_period_id,
                'contact_id' => $contact->id,
                'stage_id' => Arr::get($row, 'stage_id'),
                'type_id' => Arr::get($row, 'type_id'),
                'source_id' => Arr::get($row, 'source_id'),
                'course_id' => Arr::get($row, 'course_id'),
                'employee_id' => Arr::get($row, 'employee_id'),
                'date' => $enquiryDate,
                'status' => EnquiryStatus::OPEN,
                'remarks' => Arr::get($row, 'remarks'),
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);
        }

        $period = Period::query()
            ->whereId(auth()->user()->current_period_id)
            ->first();

        $meta = $period->meta ?? [];
        $imports['enquiry'] = Arr::get($meta, 'imports.enquiry', []);
        $imports['enquiry'][] = [
            'uuid' => $importBatchUuid,
            'total' => count($rows),
            'created_at' => now()->toDateTimeString(),
        ];

        $meta['imports'] = $imports;
        $period->meta = $meta;
        $period->save();

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $stages = Option::query()
            ->byTeam()
            ->whereType(OptionType::ENQUIRY_STAGE)
            ->get();

        $types = Option::query()
            ->byTeam()
            ->whereType(OptionType::ENQUIRY_TYPE)
            ->get();

        $sources = Option::query()
            ->byTeam()
            ->whereType(OptionType::ENQUIRY_SOURCE)
            ->get();

        $courses = Course::query()
            ->byPeriod()
            ->get();

        $employees = Employee::query()
            ->byTeam()
            ->select('code_number', 'id')
            ->get();

        $categories = Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CATEGORY)
            ->get();

        $errors = [];

        $newRows = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $studentName = Arr::get($row, 'student_name');
            $birthDate = Arr::get($row, 'birth_date');
            $gender = Arr::get($row, 'gender');
            $course = Arr::get($row, 'course');
            $fatherName = Arr::get($row, 'father_name');
            $email = Arr::get($row, 'email');
            $contactNumber = Arr::get($row, 'contact_number');
            $stage = Arr::get($row, 'stage');
            $type = Arr::get($row, 'type');
            $source = Arr::get($row, 'source');
            $employee = Arr::get($row, 'assigned_to');
            $enquiryDate = Arr::get($row, 'enquiry_date');
            $category = Arr::get($row, 'category');
            $remarks = Arr::get($row, 'remarks');

            if (! $studentName) {
                $errors[] = $this->setError($rowNo, trans('student.props.name'), 'required');
            } elseif (strlen($studentName) < 2 || strlen($studentName) > 100) {
                $errors[] = $this->setError($rowNo, trans('student.props.name'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'invalid');
            }

            if (! $contactNumber) {
                $errors[] = $this->setError($rowNo, trans('contact.props.contact_number'), 'required');
            }

            if (is_int($enquiryDate)) {
                $enquiryDate = Date::excelToDateTimeObject($enquiryDate)->format('Y-m-d');
            }

            if ($enquiryDate && ! CalHelper::validateDate($enquiryDate)) {
                $errors[] = $this->setError($rowNo, trans('reception.enquiry.props.date'), 'invalid');
            }

            if ($stage && ! in_array($stage, $stages->pluck('name')->all())) {
                $errors[] = $this->setError($rowNo, trans('reception.enquiry.stage.stage'), 'invalid');
            }

            if ($type && ! in_array($type, $types->pluck('name')->all())) {
                $errors[] = $this->setError($rowNo, trans('reception.enquiry.type.type'), 'invalid');
            }

            if ($source && ! in_array($source, $sources->pluck('name')->all())) {
                $errors[] = $this->setError($rowNo, trans('reception.enquiry.props.source'), 'invalid');
            }

            if (! $fatherName) {
                $errors[] = $this->setError($rowNo, trans('contact.props.father_name'), 'required');
            } elseif (strlen($fatherName) < 2 || strlen($fatherName) > 100) {
                $errors[] = $this->setError($rowNo, trans('contact.props.father_name'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            if (is_int($birthDate)) {
                $birthDate = Date::excelToDateTimeObject($birthDate)->format('Y-m-d');
            }

            if ($birthDate && ! CalHelper::validateDate($birthDate)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.birth_date'), 'invalid');
            }

            if (! $gender) {
                $errors[] = $this->setError($rowNo, trans('contact.props.gender'), 'required');
            } elseif ($gender && ! in_array(strtolower($gender), Gender::getKeys())) {
                $errors[] = $this->setError($rowNo, trans('contact.props.gender'), 'invalid');
            }

            if ($course && ! in_array($course, $courses->pluck('name')->all())) {
                $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'invalid');
            }

            if ($category && ! in_array($category, $categories->pluck('name')->all())) {
                $errors[] = $this->setError($rowNo, trans('contact.category.category'), 'invalid');
            }

            if ($employee && ! in_array($employee, $employees->pluck('code_number')->all())) {
                $errors[] = $this->setError($rowNo, trans('employee.employee'), 'invalid');
            }

            if ($remarks && (strlen($remarks) < 2 || strlen($remarks) > 1000)) {
                $errors[] = $this->setError($rowNo, trans('reception.enquiry.props.remarks'), 'min_max', ['min' => 2, 'max' => 1000]);
            }

            $row['employee_id'] = $employees->firstWhere('code_number', $employee)?->id;
            $row['stage_id'] = $stages->firstWhere('name', $stage)?->id;
            $row['type_id'] = $types->firstWhere('name', $type)?->id;
            $row['source_id'] = $sources->firstWhere('name', $source)?->id;
            $row['course_id'] = $courses->firstWhere('name', $course)?->id;
            $row['category_id'] = $categories->firstWhere('name', $category)?->id;

            $newRows[] = $row;
        }

        $rows = collect($newRows);

        return [$errors, $rows];
    }
}
