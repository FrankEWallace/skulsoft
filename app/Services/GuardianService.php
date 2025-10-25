<?php

namespace App\Services;

use App\Actions\CreateContact;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Contact;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GuardianService
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
        $params['source'] = 'guardian';

        $contact = (new CreateContact)->execute($params);

        \DB::commit();

        return $contact;
    }

    public function update(Request $request, Contact $contact): void
    {
        $data = $request->secured();
        $organizationName = Arr::get($data, 'organization_name');
        $designation = Arr::get($data, 'designation');

        unset($data['organization_name']);
        unset($data['designation']);

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

        $contact->update($data);
        $contact->setMeta([
            'organization_name' => $organizationName,
            'designation' => $designation,
        ]);
        $contact->save();

        \DB::commit();
    }

    public function deletable(Guardian $guardian, $validate = false): ?bool
    {
        throw ValidationException::withMessages(['message' => trans('general.errors.feature_under_development')]);

        return true;
    }
}
