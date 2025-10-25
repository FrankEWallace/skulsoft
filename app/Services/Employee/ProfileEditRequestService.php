<?php

namespace App\Services\Employee;

use App\Enums\BloodGroup;
use App\Enums\ContactEditStatus;
use App\Enums\MaritalStatus;
use App\Models\Contact;
use App\Models\ContactEditRequest;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ProfileEditRequestService
{
    public function findByUuidOrFail(Employee $employee, string $uuid): ContactEditRequest
    {
        return ContactEditRequest::query()
            ->whereUserId(auth()->id())
            ->whereModelType('Employee')
            ->whereModelId($employee->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.edit_request.edit_request'));
    }

    public function create(Request $request, Employee $employee)
    {
        $existingPendingRequest = ContactEditRequest::query()
            ->where('model_type', 'Employee')
            ->where('model_id', $employee->id)
            ->where('status', ContactEditStatus::PENDING)
            ->exists();

        if ($existingPendingRequest) {
            throw ValidationException::withMessages(['message' => trans('employee.edit_request.already_pending')]);
        }

        $contact = $employee->contact;

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

        $data = $this->prepareData($request, $contact);

        \DB::beginTransaction();

        $editRequest = ContactEditRequest::create([
            'user_id' => auth()->id(),
            'model_type' => 'Employee',
            'model_id' => $employee->id,
            'status' => ContactEditStatus::PENDING,
            'data' => $data,
        ]);

        $editRequest->addMedia($request);

        \DB::commit();
    }

    private function prepareData(Request $request, Contact $contact)
    {
        $new = $request->all();

        unset($new['media']);
        unset($new['media_token']);
        unset($new['media_hash']);
        unset($new['media_updated']);

        if ($request->blood_group) {
            $new['blood_group'] = BloodGroup::getDetail($request->blood_group)['value'] ?? '';
        }

        if ($request->marital_status) {
            $new['marital_status'] = MaritalStatus::getDetail($request->marital_status)['value'] ?? '';
        }

        $old = [
            'contact_number' => $contact->contact_number,
            'email' => $contact->email,
            'father_name' => $contact->father_name,
            'mother_name' => $contact->mother_name,
            'alternate_contact_number' => Arr::get($contact->alternate_records, 'contact_number'),
            'alternate_email' => Arr::get($contact->alternate_records, 'email'),
            'unique_id_number1' => $contact->unique_id_number1,
            'unique_id_number2' => $contact->unique_id_number2,
            'unique_id_number3' => $contact->unique_id_number3,
            'unique_id_number4' => $contact->unique_id_number4,
            'unique_id_number5' => $contact->unique_id_number5,
            'blood_group' => $contact->blood_group?->value,
            'marital_status' => $contact->marital_status?->value,
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
        //         if (! is_bool($array2[$key]) && ! is_bool($value) && empty($array2['key']) && empty($value)) {
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
