<?php

namespace App\Services;

use App\Actions\CreateContact;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Guardian;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ContactService
{
    public function preRequisite(): array
    {
        $genders = Gender::getOptions();

        $bloodGroups = BloodGroup::getOptions();

        $maritalStatuses = MaritalStatus::getOptions();

        return compact('genders', 'bloodGroups', 'maritalStatuses');
    }

    public function create(Request $request): Contact
    {
        \DB::beginTransaction();

        $params = $request->all();
        $params['source'] = 'visitor';

        $contact = (new CreateContact)->execute($params);

        \DB::commit();

        return $contact;
    }

    public function update(Request $request, Contact $contact): void
    {
        $customFields = $request->input('custom_fields', []);
        $data = $request->secured();

        $data['address'] = $contact->address;

        $request->whenHas('present_address', function ($presentAddress) use (&$data) {
            $data['address']['present'] = [
                'address_line1' => Arr::get($presentAddress, 'address_line1'),
                'address_line2' => Arr::get($presentAddress, 'address_line2'),
                'city' => Arr::get($presentAddress, 'city'),
                'state' => Arr::get($presentAddress, 'state'),
                'zipcode' => Arr::get($presentAddress, 'zipcode'),
                'country' => Arr::get($presentAddress, 'country'),
            ];
        });

        $request->whenHas('permanent_address', function ($permanentAddress) use (&$data) {
            $data['address']['permanent'] = [
                'same_as_present_address' => (bool) Arr::get($permanentAddress, 'same_as_present_address'),
                'address_line1' => Arr::get($permanentAddress, 'address_line1'),
                'address_line2' => Arr::get($permanentAddress, 'address_line2'),
                'city' => Arr::get($permanentAddress, 'city'),
                'state' => Arr::get($permanentAddress, 'state'),
                'zipcode' => Arr::get($permanentAddress, 'zipcode'),
                'country' => Arr::get($permanentAddress, 'country'),
            ];
        });

        \DB::beginTransaction();

        $contact->setMeta([
            'custom_fields' => $customFields,
        ]);

        $contact->update($data);

        \DB::commit();
    }

    public function getContactType(Contact $contact): string
    {
        $student = Student::query()
            ->whereContactId($contact->id)
            ->first();

        if ($student) {
            return 'student';
        }

        $employee = Employee::query()
            ->whereContactId($contact->id)
            ->first();

        if ($employee) {
            return 'employee';
        }

        $guardian = Guardian::query()
            ->whereContactId($contact->id)
            ->first();

        if ($guardian) {
            return 'guardian';
        }

        return 'contact';
    }

    public function deletable(Contact $contact, $validate = false): ?bool
    {
        $studentExists = \DB::table('students')
            ->whereContactId($contact->id)
            ->exists();

        if ($studentExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('contact.contact'), 'dependency' => trans('student.student')])]);
        }

        $registrationExists = \DB::table('registrations')
            ->whereContactId($contact->id)
            ->exists();

        if ($registrationExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('contact.contact'), 'dependency' => trans('student.registration.registration')])]);
        }

        $employeeExists = \DB::table('employees')
            ->whereContactId($contact->id)
            ->exists();

        if ($employeeExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('contact.contact'), 'dependency' => trans('employee.employee')])]);
        }

        $guardianExists = \DB::table('guardians')
            ->whereContactId($contact->id)
            ->orWhere('primary_contact_id', $contact->id)
            ->exists();

        if ($guardianExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('contact.contact'), 'dependency' => trans('guardian.guardian')])]);
        }

        return true;
    }
}
