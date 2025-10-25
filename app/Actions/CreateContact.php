<?php

namespace App\Actions;

use App\Models\Contact;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class CreateContact
{
    public function execute($params = []): Contact
    {
        $validate = Arr::get($params, 'validate', true);

        if ($validate) {
            Validator::make($params, [
                'email' => 'required_without:contact_number|email',
                'contact_number' => 'required_without:email|string',
            ], [], [
                'email' => trans('contact.props.email'),
                'contact_number' => trans('contact.props.contact_number'),
            ])->validate();
        }

        $name = Arr::get($params, 'name') ? $this->splitName(Arr::get($params, 'name')) : [];

        $firstName = Arr::get($name ?: $params, 'first_name');
        $middleName = Arr::get($name ?: $params, 'middle_name');
        $thirdName = Arr::get($name ?: $params, 'third_name');
        $lastName = Arr::get($name ?: $params, 'last_name');

        $teamId = Arr::get($params, 'team_id', auth()->user()?->current_team_id);

        $middleName = empty($middleName) ? null : $middleName;
        $thirdName = empty($thirdName) ? null : $thirdName;

        $email = Arr::get($params, 'email');
        $contactNumber = Arr::get($params, 'contact_number');

        $contact = Contact::query()
            ->byTeam($teamId)
            ->whereFirstName($firstName)
            ->whereMiddleName($middleName)
            ->whereThirdName($thirdName)
            ->whereLastName($lastName)
            ->where(function ($q) use ($email, $contactNumber) {
                $q->when($email && ! $contactNumber, function ($q) use ($email) {
                    $q->where('email', $email);
                })
                    ->when($contactNumber && ! $email, function ($q) use ($contactNumber) {
                        $q->where('contact_number', $contactNumber);
                    })
                    ->when($email && $contactNumber, function ($q) use ($email, $contactNumber) {
                        $q->where(function ($q) use ($email, $contactNumber) {
                            $q->where('email', $email)
                                ->orWhere('contact_number', $contactNumber);
                        });
                    });
            })
            ->first();

        if ($contact) {
            $contact->email = Arr::get($params, 'email', $contact->email);
            $contact->contact_number = Arr::get($params, 'contact_number', $contact->contact_number);
            $contact->birth_date = Arr::get($params, 'birth_date', $contact->birth_date?->value);
            $contact->father_name = Arr::get($params, 'father_name', $contact->father_name);
            $contact->mother_name = Arr::get($params, 'mother_name', $contact->mother_name);
            $contact->gender = Arr::get($params, 'gender', $contact->gender);
            $contact->save();

            return $contact;
        }

        $relation = Arr::get($params, 'relation');

        if ($relation == 'father') {
            $gender = 'male';
        } elseif ($relation == 'mother') {
            $gender = 'female';
        } else {
            $gender = Arr::get($params, 'gender');
        }

        return Contact::forceCreate([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'third_name' => $thirdName,
            'last_name' => $lastName,
            'team_id' => $teamId,
            'contact_number' => Arr::get($params, 'contact_number'),
            'email' => Arr::get($params, 'email'),
            'birth_date' => Arr::get($params, 'birth_date'),
            'father_name' => Arr::get($params, 'father_name'),
            'mother_name' => Arr::get($params, 'mother_name'),
            'gender' => $gender,
            'meta' => [
                'source' => Arr::get($params, 'source'),
            ],
        ]);
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
