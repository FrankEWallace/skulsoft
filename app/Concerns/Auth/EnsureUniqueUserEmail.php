<?php

namespace App\Concerns\Auth;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Validation\ValidationException;

trait EnsureUniqueUserEmail
{
    public function ensureEmailDoesntBelongToOtherContact(Contact $contact, string $email)
    {
        $emailBelongsToOtherContact = Contact::query()
            ->byTeam()
            ->where('id', '!=', $contact->id)
            ->where('email', $email)
            ->exists();

        if ($emailBelongsToOtherContact) {
            throw ValidationException::withMessages(['message' => trans('contact.login.email_belongs_to_other_contact')]);
        }
    }

    public function ensureEmailDoesntBelongToUserContact(string $email)
    {
        $emailBelongsToUser = User::whereEmail($email)->first();

        if ($emailBelongsToUser) {
            $userBelongsToContact = Contact::whereUserId($emailBelongsToUser->id)->exists();

            if ($userBelongsToContact) {
                throw ValidationException::withMessages(['message' => trans('contact.login.email_belongs_to_team_member')]);
            }

            if ($emailBelongsToUser->is_default) {
                throw ValidationException::withMessages(['message' => trans('contact.login.email_belongs_to_team_member')]);
            }
        }
    }
}
