<?php

namespace App\Imports\Employee;

use App\Actions\CreateContact;
use App\Concerns\HasCodeNumber;
use App\Concerns\ItemImport;
use App\Enums\Employee\Type;
use App\Enums\Gender;
use App\Helpers\CalHelper;
use App\Helpers\SysHelper;
use App\Models\Contact;
use App\Models\Employee\Department;
use App\Models\Employee\Designation;
use App\Models\Employee\Employee;
use App\Models\Employee\Record as EmployeeRecord;
use App\Models\Option;
use App\Models\Team;
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

class EmployeeImport implements ToCollection, WithHeadingRow
{
    use FormatCodeNumber, HasCodeNumber, ItemImport;

    protected $limit = 200;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('employee');

        $errors = $this->validate($rows);

        $this->checkForErrors('employee', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        \DB::beginTransaction();

        $numberPrefix = config('config.employee.code_number_prefix');
        $numberSuffix = config('config.employee.code_number_suffix');
        $digit = config('config.employee.code_number_digit', 0);

        $codeNumberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $departments = Department::globalOrByTeam()->select('id', 'name')->get();
        $designations = Designation::byTeam()->select('id', 'name')->get();
        $employmentStatuses = Option::byTeam()->whereType('employment_status')->select('id', 'name')->get();

        foreach ($rows as $row) {
            $birthDate = Arr::get($row, 'date_of_birth');

            if (is_int($birthDate)) {
                $birthDate = Date::excelToDateTimeObject($birthDate)->format('Y-m-d');
            } else {
                $birthDate = Carbon::parse($birthDate)->toDateString();
            }

            $joiningDate = Arr::get($row, 'date_of_joining');

            if (is_int($joiningDate)) {
                $joiningDate = Date::excelToDateTimeObject($joiningDate)->format('Y-m-d');
            } else {
                $joiningDate = Carbon::parse($joiningDate)->toDateString();
            }

            $contact = (new CreateContact)->execute([
                'first_name' => Arr::get($row, 'first_name'),
                'middle_name' => Arr::get($row, 'middle_name'),
                'last_name' => Arr::get($row, 'last_name'),
                'email' => Arr::get($row, 'email'),
                'contact_number' => Arr::get($row, 'contact_number'),
                'validate' => false,
                'meta' => [
                    'source' => 'employee',
                ],
            ]);

            $contact->update([
                'gender' => strtolower(Arr::get($row, 'gender')),
                'birth_date' => $birthDate,
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
                        'address_line1' => SysHelper::cleanInput(Arr::get($row, 'address_line1')),
                        'address_line2' => SysHelper::cleanInput(Arr::get($row, 'address_line2')),
                        'city' => SysHelper::cleanInput(Arr::get($row, 'city')),
                        'state' => SysHelper::cleanInput(Arr::get($row, 'state')),
                        'zipcode' => SysHelper::cleanInput(Arr::get($row, 'zipcode')),
                        'country' => SysHelper::cleanInput(Arr::get($row, 'country')),
                    ],
                ],
            ]);

            $employeeCode = Arr::get($row, 'employee_code');
            $employeeCodeFormat = Arr::get($row, 'employee_code_format') ?: $codeNumberFormat;

            $employeeCodeDigit = $this->getNumberFromFormat($employeeCode, $employeeCodeFormat);

            $numberFormat = $employeeCodeDigit ? $employeeCodeFormat : null;

            $employee = Employee::forceCreate([
                'type' => strtolower(Arr::get($row, 'type')),
                'contact_id' => $contact->id,
                'team_id' => auth()->user()->current_team_id,
                'joining_date' => $joiningDate,
                'number_format' => $numberFormat,
                'number' => $employeeCodeDigit,
                'code_number' => $employeeCode,
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);

            EmployeeRecord::forceCreate([
                'employee_id' => $employee->id,
                'start_date' => $employee->joining_date,
                'department_id' => $departments->firstWhere('name', Arr::get($row, 'department'))?->id,
                'designation_id' => $designations->firstWhere('name', Arr::get($row, 'designation'))?->id,
                'employment_status_id' => $employmentStatuses->firstWhere('name', Arr::get($row, 'employment_status'))?->id,
            ]);

            $username = Arr::get($row, 'username', Arr::get($row, 'employee_code'));
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

                $user->assignRole('staff');

                $contact->user_id = $user->id;
                $contact->save();
            }
        }

        \DB::commit();

        $team = Team::query()
            ->whereId(auth()->user()->current_team_id)
            ->first();

        $meta = $team->meta ?? [];
        $imports['employee'] = Arr::get($meta, 'imports.employee', []);
        $imports['employee'][] = [
            'uuid' => $importBatchUuid,
            'total' => count($rows),
            'created_at' => now()->toDateTimeString(),
        ];

        $meta['imports'] = $imports;
        $team->meta = $meta;
        $team->save();

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $departments = Department::globalOrByTeam()->pluck('name')->all();
        $designations = Designation::byTeam()->pluck('name')->all();
        $employmentStatuses = Option::byTeam()->whereType('employment_status')->pluck('name')->all();

        $types = Type::getKeys();

        $existingContacts = Contact::byTeam()->get()->pluck('name_with_number')->all();

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

        $existingCodeNumbers = Employee::query()
            ->select('code_number', 'number_format', 'number')
            ->byTeam()
            ->get();

        $numberPrefix = config('config.employee.code_number_prefix');
        $numberSuffix = config('config.employee.code_number_suffix');
        $digit = config('config.employee.code_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $errors = [];

        $newContacts = [];
        $newCodeNumbers = [];
        $newEmails = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $type = Arr::get($row, 'type');
            $firstName = Arr::get($row, 'first_name');
            $middleName = Arr::get($row, 'middle_name');
            $lastName = Arr::get($row, 'last_name');
            $gender = Arr::get($row, 'gender');
            $birthDate = Arr::get($row, 'date_of_birth');
            $contactNumber = Arr::get($row, 'contact_number');
            $email = Arr::get($row, 'email');

            $joiningDate = Arr::get($row, 'date_of_joining');
            $department = Arr::get($row, 'department');
            $designation = Arr::get($row, 'designation');
            $employmentStatus = Arr::get($row, 'employment_status');

            $username = Arr::get($row, 'username');
            $password = Arr::get($row, 'password');

            if (! $type) {
                $errors[] = $this->setError($rowNo, trans('employee.type'), 'required');
            } elseif ($type && ! in_array(strtolower($type), $types)) {
                $errors[] = $this->setError($rowNo, trans('employee.type'), 'invalid');
            }

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

            if (! $contactNumber) {
                $errors[] = $this->setError($rowNo, trans('contact.props.contact_number'), 'required');
            } elseif ($contactNumber && strlen($contactNumber) > 20) {
                $errors[] = $this->setError($rowNo, trans('contact.props.contact_number'), 'max', ['max' => 20]);
            }

            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'invalid');
                // Ignoring already existing contacts and using existing contact
                // } elseif ($email && in_array($email, $existingContactEmails)) {
                //     $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'exists');
            } elseif ($email && in_array($email, $existingUserEmails)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'exists');
            }

            if (! $gender) {
                $errors[] = $this->setError($rowNo, trans('contact.props.gender'), 'required');
            } elseif ($gender && ! in_array(strtolower($gender), Gender::getKeys())) {
                $errors[] = $this->setError($rowNo, trans('contact.props.gender'), 'invalid');
            }

            if (is_int($birthDate)) {
                $birthDate = Date::excelToDateTimeObject($birthDate)->format('Y-m-d');
            }

            if ($birthDate && ! CalHelper::validateDate($birthDate)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.birth_date'), 'invalid');
            }

            if (is_int($joiningDate)) {
                $joiningDate = Date::excelToDateTimeObject($joiningDate)->format('Y-m-d');
            }

            if ($joiningDate && ! CalHelper::validateDate($joiningDate)) {
                $errors[] = $this->setError($rowNo, trans('employee.props.joining_date'), 'invalid');
            }

            if (! $department) {
                $errors[] = $this->setError($rowNo, trans('employee.department.department'), 'required');
            } elseif (! in_array($department, $departments)) {
                $errors[] = $this->setError($rowNo, trans('employee.department.department'), 'invalid');
            }

            if (! $designation) {
                $errors[] = $this->setError($rowNo, trans('employee.designation.designation'), 'required');
            } elseif (! in_array($designation, $designations)) {
                $errors[] = $this->setError($rowNo, trans('employee.designation.designation'), 'invalid');
            }

            if (! $employmentStatus) {
                $errors[] = $this->setError($rowNo, trans('employee.employment_status.employment_status'), 'required');
            } elseif (! in_array($employmentStatus, $employmentStatuses)) {
                $errors[] = $this->setError($rowNo, trans('employee.employment_status.employment_status'), 'invalid');
            }

            $employeeCode = Arr::get($row, 'employee_code');
            $employeeCodeFormat = Arr::get($row, 'employee_code_format');

            if ($employeeCodeFormat) {
                $employeeCodeNumber = $this->getNumberFromFormat($employeeCode, $employeeCodeFormat);

                if (is_null($employeeCodeNumber)) {
                    $errors[] = $this->setError($rowNo, trans('employee.props.number'), 'invalid');
                }
            }

            $contact = ucwords(preg_replace('/\s+/', ' ', $firstName.' '.$middleName.' '.$lastName)).' '.$contactNumber;

            // Ignoring already existing contacts and using existing contact
            // if (in_array($contact, $existingContacts)) {
            //     $errors[] = $this->setError($rowNo, trans('employee.employee'), 'exists');
            // }

            if (in_array($contact, $newContacts)) {
                $errors[] = $this->setError($rowNo, trans('employee.employee'), 'duplicate');
            }

            if (in_array($employeeCode, $existingCodeNumbers->pluck('code_number')->all())) {
                $errors[] = $this->setError($rowNo, trans('employee.props.number'), 'exists');
            }

            if (in_array($employeeCode, $newCodeNumbers)) {
                $errors[] = $this->setError($rowNo, trans('employee.props.number'), 'duplicate');
            }

            if ($email && in_array($email, $newEmails)) {
                $errors[] = $this->setError($rowNo, trans('contact.props.email'), 'duplicate');
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
            $newCodeNumbers[] = $employeeCode;
            $newEmails[] = $email;
        }

        return $errors;
    }
}
