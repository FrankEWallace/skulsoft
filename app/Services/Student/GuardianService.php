<?php

namespace App\Services\Student;

use App\Actions\CreateContact;
use App\Concerns\Auth\EnsureUniqueUserEmail;
use App\Enums\FamilyRelation;
use App\Enums\UserStatus;
use App\Models\Guardian;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuardianService
{
    use EnsureUniqueUserEmail;

    public function preRequisite(Request $request): array
    {
        $relations = FamilyRelation::getOptions();

        $guardianTypes = [
            ['label' => trans('global.new', ['attribute' => trans('guardian.guardian')]), 'value' => 'new'],
            ['label' => trans('global.existing', ['attribute' => trans('guardian.guardian')]), 'value' => 'existing'],
        ];

        return compact('relations', 'guardianTypes');
    }

    public function create(Request $request, Student $student): Guardian
    {
        $this->validateInput($student, $request);

        $existingGuardianContactId = $request->guardian_contact_id;

        \DB::beginTransaction();

        $params = $request->all();
        $params['source'] = 'guardian';

        $contact = (new CreateContact)->execute($params);

        if ($request->type == 'new') {
            $existingGuardianContactId = $contact->id;
        }

        if ($request->create_user_account) {
            $username = empty($request->username) ? Str::slug($contact->name, '.').rand(10, 99) : $request->username;
            $email = empty($request->email) ? $username.'@example.com' : $request->email;

            $this->ensureEmailDoesntBelongToOtherContact($contact, $email);
            $this->ensureEmailDoesntBelongToUserContact($email);

            $user = User::forceCreate([
                'name' => $contact->name,
                'email' => $email,
                'username' => $username,
                'password' => bcrypt($request->password),
                'status' => UserStatus::ACTIVATED,
            ]);

            $user->assignRole('guardian');

            $contact->user_id = $user->id;
            $contact->save();
        }

        $existingGuardian = Guardian::query()
            ->where('primary_contact_id', $student->contact_id)
            ->where('contact_id', $existingGuardianContactId)
            ->first();

        if ($existingGuardian) {
            throw ValidationException::withMessages(['relation' => trans('guardian.already_exists')]);
        }

        $guardian = Guardian::firstOrCreate([
            'contact_id' => $contact->id,
            'primary_contact_id' => $student->contact_id,
        ]);

        $guardian->relation = $request->relation;
        $guardian->save();

        \DB::commit();

        return $guardian;
    }

    private function validateInput(Student $student, Request $request): void
    {
        $existingGuardian = Guardian::query()
            ->where('primary_contact_id', $student->contact_id)
            ->where('relation', $request->relation)
            ->whereIn('relation', [FamilyRelation::FATHER, FamilyRelation::MOTHER])
            ->first();

        if ($existingGuardian) {
            throw ValidationException::withMessages(['relation' => trans('guardian.already_exists')]);
        }
    }

    public function update(Request $request, Student $student, Guardian $guardian): void
    {
        $this->validateInput($student, $request);

        \DB::beginTransaction();

        $guardian->relation = $request->relation;
        $guardian->save();

        \DB::commit();
    }

    public function deletable(Student $student, Guardian $guardian): void
    {
        //
    }
}
