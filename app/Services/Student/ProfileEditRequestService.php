<?php

namespace App\Services\Student;

use App\Enums\BloodGroup;
use App\Enums\ContactEditStatus;
use App\Enums\FamilyRelation;
use App\Models\Contact;
use App\Models\ContactEditRequest;
use App\Models\Guardian;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ProfileEditRequestService
{
    public function findByUuidOrFail(Student $student, string $uuid): ContactEditRequest
    {
        return ContactEditRequest::query()
            ->whereUserId(auth()->id())
            ->whereModelType('Student')
            ->whereModelId($student->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('student.edit_request.edit_request'));
    }

    public function create(Request $request, Student $student)
    {
        $existingPendingRequest = ContactEditRequest::query()
            ->where('model_type', 'Student')
            ->where('model_id', $student->id)
            ->where('status', ContactEditStatus::PENDING)
            ->exists();

        if ($existingPendingRequest) {
            throw ValidationException::withMessages(['message' => trans('student.edit_request.already_pending')]);
        }

        $contact = $student->contact;

        $existingContact = Contact::query()
            ->byTeam()
            ->where('id', '!=', $contact->id)
            ->whereFirstName($contact->first_name)
            ->whereMiddleName($contact->first_name)
            ->whereLastName($contact->first_name)
            ->whereLastName($contact->last_name)
            ->where(function ($q) use ($request) {
                $q->where('email', $request->email)
                    ->orWhere('contact_number', $request->contact_number);
            })
            ->first();

        if ($existingContact && $existingContact->contact_number == $request->contact_number) {
            throw ValidationException::withMessages(['message' => trans('validation.unique', ['attribute' => trans('contact.props.contact_number')])]);
        }

        if ($existingContact && $existingContact->email == $request->email) {
            throw ValidationException::withMessages(['message' => trans('validation.unique', ['attribute' => trans('contact.props.email')])]);
        }

        $data = $this->prepareData($request, $student, $contact);

        \DB::beginTransaction();

        $editRequest = ContactEditRequest::create([
            'user_id' => auth()->id(),
            'model_type' => 'Student',
            'model_id' => $student->id,
            'status' => ContactEditStatus::PENDING,
            'data' => $data,
        ]);

        $editRequest->addMedia($request);

        \DB::commit();
    }

    private function prepareData(Request $request, Student $student, Contact $contact)
    {
        $new = $request->all();

        unset($new['media']);
        unset($new['media_token']);
        unset($new['media_hash']);
        unset($new['media_updated']);

        if ($request->blood_group) {
            $new['blood_group'] = BloodGroup::getDetail($request->blood_group)['value'] ?? '';
        }

        $guardians = Guardian::query()
            ->wherePrimaryContactId($student->contact_id)
            ->whereIn('relation', [FamilyRelation::FATHER, FamilyRelation::MOTHER])
            ->get();

        $fatherGuardian = $guardians->firstWhere('relation', 'father');
        $fatherContact = $fatherGuardian?->contact;
        $motherGuardian = $guardians->firstWhere('relation', 'mother');
        $motherContact = $motherGuardian?->contact;

        $old = [
            'contact_number' => $contact->contact_number,
            'email' => $contact->email,
            'alternate_contact_number' => Arr::get($contact->alternate_records, 'contact_number'),
            'alternate_email' => Arr::get($contact->alternate_records, 'email'),
            'father_contact_number' => $fatherContact?->contact_number ?? $contact->getMeta('father_contact_number'),
            'father_email' => $fatherContact?->email ?? $contact->getMeta('father_email'),
            'father_birth_date' => $fatherContact?->birth_date?->value ?? '',
            'father_occupation' => $fatherContact?->occupation ?? $contact->getMeta('father_occupation'),
            'father_annual_income' => $fatherContact?->annual_income ?? $contact->getMeta('father_annual_income'),
            'mother_contact_number' => $motherContact?->contact_number ?? $contact->getMeta('mother_contact_number'),
            'mother_email' => $motherContact?->email ?? $contact->getMeta('mother_email'),
            'mother_birth_date' => $motherContact?->birth_date?->value ?? '',
            'mother_occupation' => $motherContact?->occupation ?? $contact->getMeta('mother_occupation'),
            'mother_annual_income' => $motherContact?->annual_income ?? $contact->getMeta('mother_annual_income'),
            'unique_id_number1' => $contact->unique_id_number1,
            'unique_id_number2' => $contact->unique_id_number2,
            'unique_id_number3' => $contact->unique_id_number3,
            'unique_id_number4' => $contact->unique_id_number4,
            'unique_id_number5' => $contact->unique_id_number5,
            'blood_group' => $contact->blood_group?->value,
            'religion' => $contact->religion?->name,
            'category' => $contact->category?->name,
            'caste' => $contact->caste?->name,
            'mother_tongue' => $contact->mother_tongue,
            'birth_place' => $contact->birth_place,
            'nationality' => $contact->nationality,
            'present_address' => [
                'address_line1' => Arr::get($contact->address, 'present.address_line1'),
                'address_line2' => Arr::get($contact->address, 'present.address_line2'),
                'city' => Arr::get($contact->address, 'present.city'),
                'state' => Arr::get($contact->address, 'present.state'),
                'zipcode' => Arr::get($contact->address, 'present.zipcode'),
                'country' => Arr::get($contact->address, 'present.country'),
            ],
            'permanent_address' => [
                'same_as_present_address' => (bool) Arr::get($contact->address, 'permanent.same_as_present_address'),
                'address_line1' => Arr::get($contact->address, 'permanent.address_line1'),
                'address_line2' => Arr::get($contact->address, 'permanent.address_line2'),
                'city' => Arr::get($contact->address, 'permanent.city'),
                'state' => Arr::get($contact->address, 'permanent.state'),
                'zipcode' => Arr::get($contact->address, 'permanent.zipcode'),
                'country' => Arr::get($contact->address, 'permanent.country'),
            ],
        ];

        return [
            'old' => $this->getDifference($old, $new),
            'new' => $this->getDifference($new, $old),
        ];
    }

    private function getDifference($array1, $array2)
    {
        $difference = [];

        foreach ($array1 as $key => $value) {
            if (is_array($value) && isset($array2[$key]) && is_array($array2[$key])) {
                $recursiveDiff = $this->getDifference($value, $array2[$key]);

                if (! empty($recursiveDiff)) {
                    $difference[$key] = $recursiveDiff;
                }
            } elseif (! array_key_exists($key, $array2) || $array2[$key] != $value) {
                if (! is_bool($value) && ! is_bool(($array2[$key] ?? null)) &&
                    empty(($array2[$key] ?? null)) && empty($value)) {
                    continue;
                }

                $difference[$key] = $value;
            }
        }

        // foreach ($array1 as $key => $value) {
        //     if (is_array($value) && isset($array2[$key]) && is_array($array2[$key])) {
        //         $recursiveDiff = $this->getDifference($value, $array2[$key]);

        //         if (! empty($recursiveDiff)) {
        //             $difference[$key] = $recursiveDiff;
        //         }
        //     } elseif (! isset($array2[$key]) || $array2[$key] != $value) {
        //         if (! is_bool($array2[$key]) && ! is_bool($value) && empty($array2[$key]) && empty($value)) {
        //             continue;
        //         }

        //         $difference[$key] = $value;
        //     }
        // }

        // this part should be comment all the time
        // foreach ($array2 as $key => $value) {
        //   if (!isset($array1[$key])) {
        //     $difference[$key] = $value;
        //   }
        // }

        return $difference;
    }
}
