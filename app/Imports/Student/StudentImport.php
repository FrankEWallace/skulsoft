<?php

namespace App\Imports\Student;

use App\Actions\CreateContact;
use App\Concerns\HasCodeNumber;
use App\Concerns\ItemImport;
use App\Enums\BloodGroup;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Gender;
use App\Enums\OptionType;
use App\Enums\Student\RegistrationStatus;
use App\Helpers\CalHelper;
use App\Helpers\SysHelper;
use App\Models\Academic\Course;
use App\Models\Academic\Period;
use App\Models\Contact;
use App\Models\Option;
use App\Models\Student\Admission;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Models\User;
use App\Support\FormatCodeNumber;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StudentImport implements ToCollection, WithHeadingRow
{
    use FormatCodeNumber, HasCodeNumber, ItemImport;

    protected $limit = 1000;

    protected $trimExcept = ['date_of_admission', 'date_of_birth'];

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('student');

        $errors = $this->validate($rows);

        $this->checkForErrors('student', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        $rows = $this->trimInput($rows);

        \DB::beginTransaction();

        $courses = Course::query()
            ->with('batches')
            ->byPeriod()
            ->get();

        $categories = Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CATEGORY->value)
            ->get();

        $castes = Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CASTE->value)
            ->get();

        $religions = Option::query()
            ->byTeam()
            ->whereType(OptionType::RELIGION->value)
            ->get();

        $enrollmentTypes = Option::query()
            ->byTeam()
            ->whereType(OptionType::STUDENT_ENROLLMENT_TYPE->value)
            ->get();

        $period = Period::query()
            ->byTeam()
            ->whereId(auth()->user()->current_period_id)
            ->first();

        $admissionNumberPrefix = config('config.student.admission_number_prefix');
        $admissionNumberSuffix = config('config.student.admission_number_suffix');
        $admissionNumberDigit = config('config.student.admission_number_digit', 0);
        $admissionNumberFormat = $admissionNumberPrefix.'%NUMBER%'.$admissionNumberSuffix;

        $credentials = [];
        foreach ($rows as $row) {
            $category = $categories->firstWhere('name', trim(Arr::get($row, 'category')));
            $caste = $castes->firstWhere('name', Arr::get($row, 'caste'));
            $religion = $religions->firstWhere('name', Arr::get($row, 'religion'));

            $enrollmentType = $enrollmentTypes->firstWhere('name', Arr::get($row, 'enrollment_type'));

            $birthDate = Arr::get($row, 'date_of_birth');

            if (empty($birthDate)) {
                $birthDate = null;
            } elseif (is_int(Arr::get($row, 'date_of_birth'))) {
                $birthDate = Date::excelToDateTimeObject(Arr::get($row, 'date_of_birth'))->format('Y-m-d');
            } else {
                $birthDate = Carbon::parse(Arr::get($row, 'date_of_birth'))->toDateString();
            }

            if (is_int(Arr::get($row, 'date_of_admission'))) {
                $admissionDate = Date::excelToDateTimeObject(Arr::get($row, 'date_of_admission'))->format('Y-m-d');
            } else {
                $admissionDate = Carbon::parse(Arr::get($row, 'date_of_admission'))->toDateString();
            }

            $bloodGroup = BloodGroup::tryFromAliases(Arr::get($row, 'blood_group'))?->value;

            $contact = (new CreateContact)->execute([
                'first_name' => Arr::get($row, 'first_name'),
                'middle_name' => Arr::get($row, 'middle_name'),
                'last_name' => Arr::get($row, 'last_name'),
                'email' => Arr::get($row, 'email'),
                'contact_number' => Arr::get($row, 'contact_number'),
                'validate' => false,
            ]);

            $address = SysHelper::cleanInput(Arr::get($row, 'address'));
            $addressLine1 = SysHelper::cleanInput(Arr::get($row, 'address_line1'));
            $addressLine2 = SysHelper::cleanInput(Arr::get($row, 'address_line2'));
            $city = SysHelper::cleanInput(Arr::get($row, 'city'));
            $state = SysHelper::cleanInput(Arr::get($row, 'state'));
            $zipcode = SysHelper::cleanInput(Arr::get($row, 'zipcode'));
            $country = SysHelper::cleanInput(Arr::get($row, 'country'));

            if ($address) {
                $addressLine1 = $address;
            }

            $contact->update([
                'gender' => strtolower(Arr::get($row, 'gender')),
                'blood_group' => $bloodGroup,
                'birth_date' => $birthDate,
                'category_id' => $category?->id,
                'caste_id' => $caste?->id,
                'religion_id' => $religion?->id,
                'father_name' => SysHelper::cleanInput(Arr::get($row, 'father_name')),
                'mother_name' => SysHelper::cleanInput(Arr::get($row, 'mother_name')),
                'unique_id_number1' => SysHelper::cleanInput(Arr::get($row, 'unique_id1')),
                'unique_id_number2' => SysHelper::cleanInput(Arr::get($row, 'unique_id2')),
                'unique_id_number3' => SysHelper::cleanInput(Arr::get($row, 'unique_id3')),
                'unique_id_number4' => SysHelper::cleanInput(Arr::get($row, 'unique_id4')),
                'unique_id_number5' => SysHelper::cleanInput(Arr::get($row, 'unique_id5')),
                'nationality' => SysHelper::cleanInput(Arr::get($row, 'nationality')),
                'mother_tongue' => SysHelper::cleanInput(Arr::get($row, 'mother_tongue')),
                'birth_place' => SysHelper::cleanInput(Arr::get($row, 'birth_place')),
                'alternate_records' => [
                    'contact_number' => SysHelper::cleanInput(Arr::get($row, 'alternate_contact_number')),
                    'email' => SysHelper::cleanInput(Arr::get($row, 'alternate_email')),
                ],
                'address' => [
                    'present' => [
                        'address_line1' => $addressLine1,
                        'address_line2' => $addressLine2,
                        'city' => $city,
                        'state' => $state,
                        'zipcode' => $zipcode,
                        'country' => $country,
                    ],
                ],
                'meta' => [
                    'source' => 'student',
                ],
            ]);

            $registration = Registration::forceCreate([
                'contact_id' => $contact->id,
                'period_id' => auth()->user()->current_period_id,
                'date' => $admissionDate,
                'fee' => 0,
                'payment_status' => PaymentStatus::NA,
                'code_number' => Arr::get($row, 'admission_number'),
                'status' => RegistrationStatus::APPROVED,
            ]);

            $batch = $courses
                ->firstWhere('name', Arr::get($row, 'course'))
                ->batches
                ->firstWhere('name', Arr::get($row, 'batch'));

            $studentAdmissionNumber = Arr::get($row, 'admission_number');
            $studentAdmissionNumberFormat = Arr::get($row, 'admission_number_format') ?: $admissionNumberFormat;

            $studentAdmissionNumberDigit = $this->getNumberFromFormat($studentAdmissionNumber, $studentAdmissionNumberFormat);

            $numberFormat = $studentAdmissionNumberDigit ? $studentAdmissionNumberFormat : null;

            $rollNumberPrefix = $batch->getConfig('roll_number_prefix');
            $rollNumber = Arr::get($row, 'roll_number');

            $number = null;
            if ($rollNumber) {
                if ($rollNumberPrefix) {
                    $number = Str::after($rollNumber, $rollNumberPrefix);
                } else {
                    $number = preg_replace('/[^0-9]/', '', $rollNumber);
                }

                if (! is_numeric($number)) {
                    $number = null;
                }
            }

            $admissionType = Arr::get($row, 'admission_type', 'old');

            $startDate = null;
            if (strtolower($admissionType) == 'new') {
                $startDate = $admissionDate;
            } else {
                $startDate = $admissionDate > $period->start_date ? $admissionDate : $period->start_date;
            }

            $admission = Admission::forceCreate([
                'number_format' => $numberFormat,
                'number' => $studentAdmissionNumberDigit,
                'code_number' => Arr::get($row, 'admission_number'),
                'registration_id' => $registration->id,
                'batch_id' => null,
                'joining_date' => $admissionDate,
            ]);

            $student = Student::forceCreate([
                'admission_id' => $admission->id,
                'period_id' => $registration->period_id,
                'batch_id' => $batch->id,
                'contact_id' => $registration->contact_id,
                'number' => $number,
                'roll_number' => $rollNumber,
                'start_date' => $startDate,
                'enrollment_type_id' => $enrollmentType?->id,
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);

            $username = Arr::get($row, 'username', Arr::get($row, 'admission_number'));
            $password = Arr::get($row, 'password');

            if ($username && $password) {
                $user = User::forceCreate([
                    'name' => $contact->name,
                    'email' => empty($contact->email) ? $username.'@example.com' : $contact->email,
                    'username' => $username,
                    'password' => bcrypt($password),
                    'email_verified_at' => now()->toDateString(),
                    'status' => 'activated',
                    'meta' => ['current_team_id' => auth()->user()->current_team_id],
                ]);

                $user->assignRole('student');

                $contact->user_id = $user->id;
                $contact->save();
            }
        }

        \DB::commit();

        $period = Period::query()
            ->whereId(auth()->user()->current_period_id)
            ->first();

        $meta = $period->meta ?? [];
        $imports['student'] = Arr::get($meta, 'imports.student', []);
        $imports['student'][] = [
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
        $rows = $this->trimInput($rows);

        $courses = Course::query()
            ->with('batches')
            ->byPeriod()
            ->get();

        $categories = Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CATEGORY->value)
            ->get()
            ->pluck('name')
            ->all();

        $castes = Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CASTE->value)
            ->get()
            ->pluck('name')
            ->all();

        $religions = Option::query()
            ->byTeam()
            ->whereType(OptionType::RELIGION->value)
            ->get()
            ->pluck('name')
            ->all();

        $enrollmentTypes = Option::query()
            ->byTeam()
            ->whereType(OptionType::STUDENT_ENROLLMENT_TYPE->value)
            ->get()
            ->pluck('name')
            ->all();

        $existingAdmissions = Admission::query()
            ->select('code_number', 'number_format', 'number')
            ->byTeam()
            ->get();

        $admissionNumberPrefix = config('config.student.admission_number_prefix');
        $admissionNumberSuffix = config('config.student.admission_number_suffix');
        $admissionNumberDigit = config('config.student.admission_number_digit', 0);

        $admissionNumberFormat = $admissionNumberPrefix.'%NUMBER%'.$admissionNumberSuffix;

        $existingContacts = Contact::query()
            ->byTeam()
            ->get()
            ->pluck('name_with_number')
            ->all();

        $existingContactEmails = Contact::query()
            ->byTeam()
            ->get()
            ->pluck('email')
            ->all();

        $existingUserEmails = User::query()
            ->get()
            ->pluck('email')
            ->all();

        $existingUsernames = User::query()
            ->get()
            ->pluck('username')
            ->all();

        $errors = [];

        $newContacts = [];
        $newAdmissionNumbers = [];
        $newEmails = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $firstName = Arr::get($row, 'first_name');
            $middleName = Arr::get($row, 'middle_name');
            $lastName = Arr::get($row, 'last_name');
            $gender = Arr::get($row, 'gender');
            $birthDate = Arr::get($row, 'date_of_birth');
            $contactNumber = Arr::get($row, 'contact_number');
            $email = Arr::get($row, 'email');

            $fatherName = Arr::get($row, 'father_name');
            $motherName = Arr::get($row, 'mother_name');

            $bloodGroup = Arr::get($row, 'blood_group');
            $category = trim(Arr::get($row, 'category'));
            $caste = Arr::get($row, 'caste');
            $religion = Arr::get($row, 'religion');
            $enrollmentType = Arr::get($row, 'enrollment_type');

            $address = Arr::get($row, 'address');
            $addressLine1 = Arr::get($row, 'address_line1');
            $addressLine2 = Arr::get($row, 'address_line2');
            $city = Arr::get($row, 'city');
            $state = Arr::get($row, 'state');
            $zipcode = Arr::get($row, 'zipcode');
            $country = Arr::get($row, 'country');

            $admissionDate = Arr::get($row, 'date_of_admission');
            $course = Arr::get($row, 'course');
            $batch = Arr::get($row, 'batch');

            $username = Arr::get($row, 'username');
            $password = Arr::get($row, 'password');

            if (! $firstName) {
                $errors[] = $this->setError($rowNo, trans('contact.props.first_name'), 'required');
            } elseif (strlen($firstName) < 1 || strlen($firstName) > 100) {
                $errors[] = $this->setError($rowNo, trans('contact.props.first_name'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            if ($lastName && strlen($lastName) > 100) {
                $errors[] = $this->setError($rowNo, trans('contact.props.last_name'), 'max', ['max' => 100]);
            }

            if ($middleName && strlen($middleName) > 100) {
                $errors[] = $this->setError($rowNo, trans('contact.props.middle_name'), 'max', ['max' => 100]);
            }

            // if (! $fatherName) {
            //     $errors[] = $this->setError($rowNo, trans('contact.props.father_name'), 'required');
            // } elseif (strlen($fatherName) < 2 || strlen($fatherName) > 100) {
            //     $errors[] = $this->setError($rowNo, trans('contact.props.father_name'), 'min_max', ['min' => 2, 'max' => 100]);
            // }

            if ($fatherName && (strlen($fatherName) < 2 || strlen($fatherName) > 100)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.father_name'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            // if (! $motherName) {
            //     $errors[] = $this->setError($rowNo, trans('contact.props.mother_name'), 'required');
            // } elseif (strlen($motherName) < 2 || strlen($motherName) > 100) {
            //     $errors[] = $this->setError($rowNo, trans('contact.props.mother_name'), 'min_max', ['min' => 2, 'max' => 100]);
            // }

            if ($motherName && (strlen($motherName) < 2 || strlen($motherName) > 100)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.mother_name'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            if ($address && strlen($address) > 250) {
                $errors[] = $this->setError($rowNo, trans('contact.props.address.address'), 'max', ['max' => 100]);
            }

            if ($addressLine1 && strlen($addressLine1) > 250) {
                $errors[] = $this->setError($rowNo, trans('contact.props.address.address_line1'), 'max', ['max' => 100]);
            }

            if ($addressLine2 && strlen($addressLine2) > 100) {
                $errors[] = $this->setError($rowNo, trans('contact.props.address.address_line2'), 'max', ['max' => 100]);
            }

            if ($city && strlen($city) > 50) {
                $errors[] = $this->setError($rowNo, trans('contact.props.address.city'), 'max', ['max' => 50]);
            }

            if ($state && strlen($state) > 50) {
                $errors[] = $this->setError($rowNo, trans('contact.props.address.state'), 'max', ['max' => 50]);
            }

            if ($zipcode && strlen($zipcode) > 10) {
                $errors[] = $this->setError($rowNo, trans('contact.props.address.zipcode'), 'max', ['max' => 10]);
            }

            if ($country && strlen($country) > 20) {
                $errors[] = $this->setError($rowNo, trans('contact.props.address.country'), 'max', ['max' => 20]);
            }

            if (! $contactNumber) {
                $errors[] = $this->setError($rowNo, trans('contact.props.contact_number'), 'required');
            } elseif ($contactNumber && strlen($contactNumber) > 20) {
                $errors[] = $this->setError($rowNo, trans('contact.props.contact_number'), 'max', ['max' => 20]);
            }

            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'invalid');
                // Ignoring already existing contact emails and using existing email
                // } elseif ($email && in_array($email, $existingContactEmails)) {
                //     $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'exists');
            } elseif ($email && in_array($email, $existingUserEmails)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'exists');
            } elseif ($email && in_array($email, $newEmails)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'duplicate');
            }

            if (! $gender) {
                $errors[] = $this->setError($rowNo, trans('contact.props.gender'), 'required');
            } elseif ($gender && ! in_array(strtolower($gender), Gender::getKeys())) {
                $errors[] = $this->setError($rowNo, trans('contact.props.gender'), 'invalid');
            }

            if ($bloodGroup && ! in_array(strtolower($bloodGroup), BloodGroup::getKeysWithAlias())) {
                $errors[] = $this->setError($rowNo, trans('contact.props.blood_group'), 'invalid');
            }

            if ($category && ! in_array($category, $categories)) {
                $errors[] = $this->setError($rowNo, trans('contact.category.category'), 'invalid');
            }

            if ($caste && ! in_array($caste, $castes)) {
                $errors[] = $this->setError($rowNo, trans('contact.caste.caste'), 'invalid');
            }

            if ($religion && ! in_array($religion, $religions)) {
                $errors[] = $this->setError($rowNo, trans('contact.religion.religion'), 'invalid');
            }

            if ($enrollmentType && ! in_array($enrollmentType, $enrollmentTypes)) {
                $errors[] = $this->setError($rowNo, trans('student.enrollment_type.enrollment_type'), 'invalid');
            }

            if (is_int($birthDate)) {
                $birthDate = Date::excelToDateTimeObject($birthDate)->format('Y-m-d');
            }

            if ($birthDate && ! CalHelper::validateDate($birthDate)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.birth_date'), 'invalid');
            }

            if (! $admissionDate) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.date'), 'required');
            }

            if (is_int($admissionDate)) {
                $admissionDate = Date::excelToDateTimeObject($admissionDate)->format('Y-m-d');
            }

            if ($admissionDate && ! CalHelper::validateDate($admissionDate)) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.date'), 'invalid');
            }

            $selectedCourse = null;
            if (! $course) {
                $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'required');
            } else {
                $selectedCourse = $courses->firstWhere('name', $course);
                if (! $selectedCourse) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'invalid');
                } else {
                }
            }

            if (! $batch) {
                $errors[] = $this->setError($rowNo, trans('academic.batch.batch'), 'required');
            } elseif ($batch && $selectedCourse) {
                $selectedBatch = $selectedCourse->batches->firstWhere('name', $batch);

                if (! $selectedBatch) {
                    $errors[] = $this->setError($rowNo, trans('academic.batch.batch'), 'invalid');
                } else {

                }
            }

            $studentAdmissionNumber = Arr::get($row, 'admission_number');
            $studentAdmissionNumberFormat = Arr::get($row, 'admission_number_format');

            if ($studentAdmissionNumberFormat) {
                $studentAdmissionNumberValue = $this->getNumberFromFormat($studentAdmissionNumber, $studentAdmissionNumberFormat);

                if (is_null($studentAdmissionNumberValue)) {
                    $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'invalid');
                }
            }

            $contact = ucwords(preg_replace('/\s+/', ' ', $firstName.' '.$middleName.' '.$lastName)).' '.$contactNumber;

            // Ignoring already existing contacts and using existing contact
            // if (in_array($contact, $existingContacts)) {
            //     $errors[] = $this->setError($rowNo, trans('student.student'), 'exists');
            // }

            if (in_array($contact, $newContacts)) {
                $errors[] = $this->setError($rowNo, trans('student.student'), 'duplicate');
            }

            if (in_array($studentAdmissionNumber, $existingAdmissions->pluck('code_number')->all())) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'exists');
            }

            if (in_array($studentAdmissionNumber, $newAdmissionNumbers)) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'duplicate');
            }

            if ($username) {
                if (in_array($username, $existingUsernames)) {
                    $errors[] = $this->setError($rowNo, trans('auth.login.props.username'), 'exists');
                } else {
                    array_push($existingUsernames, $username);
                }

                $validUsername = preg_match('/^(?=.{4,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $username);
                if (! $validUsername) {
                    $errors[] = $this->setError($rowNo, trans('auth.login.props.username'), 'invalid');
                }

                if (! $password) {
                    $errors[] = $this->setError($rowNo, trans('auth.login.props.password'), 'required');
                } elseif (strlen($password) < 6 || strlen($password) > 32) {
                    $errors[] = $this->setError($rowNo, trans('auth.login.props.password'), 'min_max', ['min' => 6, 'max' => 32]);
                }
            }

            $newContacts[] = $contact;
            $newAdmissionNumbers[] = $studentAdmissionNumber;
            $newEmails[] = $email;
        }

        return $errors;
    }

    private function trimInput(Collection $rows)
    {
        return collect($rows)
            ->map(function ($row) {
                return collect($row)->map(function ($value, $key) {
                    return in_array($key, $this->trimExcept) ? $value : trim($value);
                })->all();
            })->all();
    }
}
