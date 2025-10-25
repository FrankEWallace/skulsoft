<?php

namespace App\Services\Employee;

use App\Enums\BloodGroup;
use App\Enums\ContactEditStatus;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Models\ContactEditRequest;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EditRequestActionService
{
    public function action(Request $request, ContactEditRequest $editRequest)
    {
        $request->validate([
            'status' => 'required|in:approve,reject',
            'comment' => 'required_if:status,reject|max:200',
        ]);

        if ($editRequest->processed_at->value) {
            throw ValidationException::withMessages([
                'message' => trans('employee.edit_request.already_processed'),
            ]);
        }

        if ($request->status == 'reject') {
            $editRequest->processed_at = now()->toDateTimeString();
            $editRequest->setMeta([
                'processed_by' => auth()->user()?->name,
            ]);
            $editRequest->comment = $request->comment;
            $editRequest->status = ContactEditStatus::REJECTED;
            $editRequest->save();

            return;
        }

        // throw ValidationException::withMessages(['message' => trans('general.errors.feature_under_development')]);

        $employee = $editRequest->model;
        $contact = $employee->contact;

        \DB::beginTransaction();

        $alternateRecords = $contact->alternate_records;
        $alternateRecords['contact_number'] = Arr::get($editRequest->data, 'new.alternate_contact_number', Arr::get($contact->alternate_records, 'contact_number'));

        $meta = $contact->meta;

        $contact->contact_number = Arr::get($editRequest->data, 'new.contact_number', $contact->contact_number);
        $contact->alternate_records = $alternateRecords;
        $contact->email = Arr::get($editRequest->data, 'new.email', $contact->email);
        $contact->father_name = Arr::get($editRequest->data, 'new.father_name', $contact->father_name);
        $contact->mother_name = Arr::get($editRequest->data, 'new.mother_name', $contact->mother_name);
        $contact->unique_id_number1 = Arr::get($editRequest->data, 'new.unique_id_number1', $contact->unique_id_number1);
        $contact->unique_id_number2 = Arr::get($editRequest->data, 'new.unique_id_number2', $contact->unique_id_number2);
        $contact->unique_id_number3 = Arr::get($editRequest->data, 'new.unique_id_number3', $contact->unique_id_number3);
        $contact->unique_id_number4 = Arr::get($editRequest->data, 'new.unique_id_number4', $contact->unique_id_number4);
        $contact->unique_id_number5 = Arr::get($editRequest->data, 'new.unique_id_number5', $contact->unique_id_number5);
        $contact->birth_place = Arr::get($editRequest->data, 'new.birth_place', $contact->birth_place);
        $contact->nationality = Arr::get($editRequest->data, 'new.nationality', $contact->nationality);
        $contact->mother_tongue = Arr::get($editRequest->data, 'new.mother_tongue', $contact->mother_tongue);
        $contact->meta = $meta;

        if (Arr::get($editRequest->data, 'new.blood_group')) {
            $contact->blood_group = BloodGroup::tryFrom(Arr::get($editRequest->data, 'new.blood_group'));
        }

        if (Arr::get($editRequest->data, 'new.marital_status')) {
            $contact->marital_status = MaritalStatus::tryFrom(Arr::get($editRequest->data, 'new.marital_status'));
        }

        if (Arr::get($editRequest->data, 'new.religion')) {
            $contact->religion_id = Option::query()
                ->byTeam()
                ->where('type', OptionType::RELIGION->value)
                ->whereName(Arr::get($editRequest->data, 'new.religion'))
                ->first()?->id;
        }

        if (Arr::get($editRequest->data, 'new.category')) {
            $contact->category_id = Option::query()
                ->byTeam()
                ->where('type', OptionType::MEMBER_CATEGORY->value)
                ->whereName(Arr::get($editRequest->data, 'new.category'))
                ->first()?->id;
        }

        if (Arr::get($editRequest->data, 'new.caste')) {
            $contact->caste_id = Option::query()
                ->byTeam()
                ->where('type', OptionType::MEMBER_CASTE->value)
                ->whereName(Arr::get($editRequest->data, 'new.caste'))
                ->first()?->id;
        }

        $sameAsPresentAddress = (bool) Arr::get($contact->address, 'permanent.same_as_present_address');

        if (Arr::has($editRequest->data, 'new.permanent_address.same_as_present_address')) {
            $sameAsPresentAddress = (bool) Arr::get($editRequest->data, 'new.permanent_address.same_as_present_address');
        }

        if ($sameAsPresentAddress) {
            $permanentAddress = [
                'same_as_present_address' => true,
                'address_line1' => '',
                'address_line2' => '',
                'city' => '',
                'state' => '',
                'zipcode' => '',
                'country' => '',
            ];
        } else {
            $permanentAddress = [
                'same_as_present_address' => false,
                'address_line1' => Arr::get($editRequest->data, 'new.permanent_address.address_line1', Arr::get($contact->address, 'permanent.address_line1')),
                'address_line2' => Arr::get($editRequest->data, 'new.permanent_address.address_line2', Arr::get($contact->address, 'permanent.address_line2')),
                'city' => Arr::get($editRequest->data, 'new.permanent_address.city', Arr::get($contact->address, 'permanent.city')),
                'state' => Arr::get($editRequest->data, 'new.permanent_address.state', Arr::get($contact->address, 'permanent.state')),
                'zipcode' => Arr::get($editRequest->data, 'new.permanent_address.zipcode', Arr::get($contact->address, 'permanent.zipcode')),
                'country' => Arr::get($editRequest->data, 'new.permanent_address.country', Arr::get($contact->address, 'permanent.country')),
            ];
        }

        $contact->address = [
            'present' => [
                'address_line1' => Arr::get($editRequest->data, 'new.present_address.address_line1', Arr::get($contact->address, 'present.address_line1')),
                'address_line2' => Arr::get($editRequest->data, 'new.present_address.address_line2', Arr::get($contact->address, 'present.address_line2')),
                'city' => Arr::get($editRequest->data, 'new.present_address.city', Arr::get($contact->address, 'present.city')),
                'state' => Arr::get($editRequest->data, 'new.present_address.state', Arr::get($contact->address, 'present.state')),
                'zipcode' => Arr::get($editRequest->data, 'new.present_address.zipcode', Arr::get($contact->address, 'present.zipcode')),
                'country' => Arr::get($editRequest->data, 'new.present_address.country', Arr::get($contact->address, 'present.country')),
            ],
            'permanent' => $permanentAddress,
        ];

        $contact->setMeta([
            'last_edit_request_process_date' => today()->toDateString(),
        ]);

        $contact->save();

        $editRequest->processed_at = now()->toDateTimeString();
        $editRequest->setMeta([
            'processed_by' => auth()->user()?->name,
        ]);
        $editRequest->comment = $request->comment;
        $editRequest->status = ContactEditStatus::APPROVED;
        $editRequest->save();

        \DB::commit();
    }
}
