<?php

namespace App\Actions;

use App\Helpers\CalHelper;
use App\Models\Contact;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateContact
{
    public function execute(Contact $contact, array $params = []): Contact
    {
        if (Arr::get($params, 'email') && Arr::get($params, 'contact_number')) {
            Validator::make($params, [
                'email' => 'required_without:contact_number|email|max:50',
                'contact_number' => 'required_without:email|string|max:20',
            ], [], [
                'email' => trans('contact.props.email'),
                'contact_number' => trans('contact.props.contact_number'),
            ])->validate();
        }

        if (Arr::get($params, 'email', $contact->email)) {
            $existingContact = Contact::query()
                ->byTeam()
                ->where('id', '!=', $contact->id)
                ->whereFirstName(Arr::get($params, 'first_name', $contact->first_name))
                ->whereLastName(Arr::get($params, 'last_name', $contact->last_name))
                ->whereEmail(Arr::get($params, 'email', $contact->email))
                ->first();

            if ($existingContact) {
                throw ValidationException::withMessages(['message' => trans('validation.unique', ['attribute' => trans('contact.props.email')])]);
            }
        }

        if (Arr::get($params, 'contact_number', $contact->contact_number)) {
            $existingContact = Contact::query()
                ->byTeam()
                ->where('id', '!=', $contact->id)
                ->whereFirstName(Arr::get($params, 'first_name', $contact->first_name))
                ->whereLastName(Arr::get($params, 'last_name', $contact->last_name))
                ->whereContactNumber(Arr::get($params, 'contact_number', $contact->contact_number))
                ->first();

            if ($existingContact) {
                throw ValidationException::withMessages(['message' => trans('validation.unique', ['attribute' => trans('contact.props.contact_number')])]);
            }
        }

        $name = [];
        if (Arr::get($params, 'name')) {
            $name = Arr::get($params, 'name') ? $this->splitName(Arr::get($params, 'name')) : [];
        }

        $firstName = Arr::get($name ?: $params, 'first_name');
        $middleName = Arr::get($name ?: $params, 'middle_name');
        $thirdName = Arr::get($name ?: $params, 'third_name');
        $lastName = Arr::get($name ?: $params, 'last_name');

        $contact->first_name = $firstName ?: $contact->first_name;
        $contact->middle_name = $middleName;
        $contact->third_name = $thirdName;
        $contact->last_name = $lastName;
        $contact->gender = Arr::exists($params, 'gender') ? Arr::get($params, 'gender') : $contact->gender;
        $contact->birth_date = Arr::exists($params, 'birth_date') ? CalHelper::toDate(Arr::get($params, 'birth_date')) : $contact->birth_date;
        $contact->father_name = Arr::exists($params, 'father_name') ? Arr::get($params, 'father_name') : $contact->father_name;
        $contact->mother_name = Arr::exists($params, 'mother_name') ? Arr::get($params, 'mother_name') : $contact->mother_name;
        $contact->unique_id_number1 = Arr::exists($params, 'unique_id_number1') ? Arr::get($params, 'unique_id_number1') : $contact->unique_id_number1;
        $contact->unique_id_number2 = Arr::exists($params, 'unique_id_number2') ? Arr::get($params, 'unique_id_number2') : $contact->unique_id_number2;
        $contact->unique_id_number3 = Arr::exists($params, 'unique_id_number3') ? Arr::get($params, 'unique_id_number3') : $contact->unique_id_number3;
        $contact->unique_id_number4 = Arr::exists($params, 'unique_id_number4') ? Arr::get($params, 'unique_id_number4') : $contact->unique_id_number4;
        $contact->unique_id_number5 = Arr::exists($params, 'unique_id_number5') ? Arr::get($params, 'unique_id_number5') : $contact->unique_id_number5;
        $contact->birth_place = Arr::exists($params, 'birth_place') ? Arr::get($params, 'birth_place') : $contact->birth_place;
        $contact->nationality = Arr::exists($params, 'nationality') ? Arr::get($params, 'nationality') : $contact->nationality;
        $contact->mother_tongue = Arr::exists($params, 'mother_tongue') ? Arr::get($params, 'mother_tongue') : $contact->mother_tongue;
        $contact->contact_number = Arr::exists($params, 'contact_number') ? Arr::get($params, 'contact_number') : $contact->contact_number;
        $contact->email = Arr::exists($params, 'email') ? Arr::get($params, 'email') : $contact->email;

        $contact->caste_id = Arr::exists($params, 'caste_id') ? Arr::get($params, 'caste_id') : $contact->caste_id;
        $contact->religion_id = Arr::exists($params, 'religion_id') ? Arr::get($params, 'religion_id') : $contact->religion_id;
        $contact->category_id = Arr::exists($params, 'category_id') ? Arr::get($params, 'category_id') : $contact->category_id;
        $contact->blood_group = Arr::exists($params, 'blood_group') ? Arr::get($params, 'blood_group') : $contact->blood_group;
        $contact->locality = Arr::exists($params, 'locality') ? Arr::get($params, 'locality') : $contact->locality;
        $contact->marital_status = Arr::exists($params, 'marital_status') ? Arr::get($params, 'marital_status') : $contact->marital_status;
        $contact->occupation = Arr::exists($params, 'occupation') ? Arr::get($params, 'occupation') : $contact->occupation;
        $contact->annual_income = Arr::exists($params, 'annual_income') ? Arr::get($params, 'annual_income') : $contact->annual_income;

        $contact->alternate_records = [
            'contact_number' => Arr::has($params, 'alternate_records.contact_number') ? Arr::get($params, 'alternate_records.contact_number') : Arr::get($contact->alternate_records, 'contact_number'),
            'email' => Arr::has($params, 'alternate_records.email') ? Arr::get($params, 'alternate_records.email') : Arr::get($contact->alternate_records, 'email'),
        ];

        $contact->address = [
            'present' => $this->getAddress($params, $contact, 'present_address'),
            'permanent' => $this->getAddress($params, $contact, 'permanent_address'),
        ];

        if (Arr::has($params, 'custom_fields')) {
            $customFields = $params['custom_fields'];

            $contact->setMeta([
                'custom_fields' => $customFields,
            ]);
        }

        $contact->save();

        return $contact;
    }

    private function getAddress(array $params, Contact $contact, string $type = 'present_address'): array
    {
        $typeShort = $type === 'present_address' ? 'present' : 'permanent';

        $address = [
            'address_line1' => Arr::has($params, $type.'.address_line1') ? Arr::get($params, $type.'.address_line1') : Arr::get($contact->address, $typeShort.'.address_line1'),
            'address_line2' => Arr::has($params, $type.'.address_line2') ? Arr::get($params, $type.'.address_line2') : Arr::get($contact->address, $typeShort.'.address_line2'),
            'city' => Arr::has($params, $type.'.city') ? Arr::get($params, $type.'.city') : Arr::get($contact->address, $typeShort.'.city'),
            'state' => Arr::has($params, $type.'.state') ? Arr::get($params, $type.'.state') : Arr::get($contact->address, $typeShort.'.state'),
            'zipcode' => Arr::has($params, $type.'.zipcode') ? Arr::get($params, $type.'.zipcode') : Arr::get($contact->address, $typeShort.'.zipcode'),
            'country' => Arr::has($params, $type.'.country') ? Arr::get($params, $type.'.country') : Arr::get($contact->address, $typeShort.'.country'),
        ];

        if ($type === 'permanent_address') {
            $address['same_as_present_address'] = Arr::has($params, $type.'.same_as_present_address') ? (bool) Arr::get($params, $type.'.same_as_present_address') : (bool) Arr::get($contact->address, 'permanent.same_as_present_address');
        }

        return $address;
    }

    private function splitName($string): array
    {
        $array = explode(' ', $string);
        $num = count($array);
        $first_name = $middle_name = $third_name = $last_name = null;

        if ($num == 1) {
            [$first_name] = $array;
        } elseif ($num == 2) {
            [$first_name, $last_name] = $array;
        } elseif ($num == 3) {
            [$first_name, $middle_name, $last_name] = $array;
        } else {
            [$first_name, $middle_name, $third_name, $last_name] = $array;
        }

        return compact(
            'first_name',
            'middle_name',
            'third_name',
            'last_name'
        );
    }
}
