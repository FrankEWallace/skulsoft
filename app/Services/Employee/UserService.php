<?php

namespace App\Services\Employee;

use App\Concerns\Auth\EnsureUniqueUserEmail;
use App\Enums\UserStatus;
use App\Http\Resources\UserSummaryResource;
use App\Models\Academic\Period;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as SpatieRole;

class UserService
{
    use EnsureUniqueUserEmail;

    public function confirm(Request $request, Employee $employee): ?UserSummaryResource
    {
        $request->validate([
            'email' => 'required|email',
        ], [], [
            'email' => trans('contact.login.props.email'),
        ]);

        $this->ensureEmailDoesntBelongToUserContact($request->email);

        $this->ensureEmailDoesntBelongToOtherContact($employee->contact, $request->email);

        $user = User::whereEmail($request->email)->first();

        return $user ? UserSummaryResource::make($user) : null;
    }

    public function fetch(Employee $employee): array|UserSummaryResource
    {
        $employee->load('contact.user.roles');

        if (! $employee->user_id) {
            return [];
        }

        return UserSummaryResource::make($employee->contact->user);
    }

    public function create(Request $request, Employee $employee)
    {
        $this->ensureEmailDoesntBelongToOtherContact($employee->contact, $request->email);

        $this->ensureEmailDoesntBelongToUserContact($request->email);

        $employee->load('contact.user');

        $user = $employee->contact?->user;

        if ($user) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        \DB::beginTransaction();

        $user = User::forceCreate([
            'name' => $employee->contact->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'status' => UserStatus::ACTIVATED->value,
        ]);

        $user->assignRole(SpatieRole::find($request->role_ids));

        $contact = $employee->contact;
        $contact->user_id = $user->id;
        $contact->save();

        \DB::commit();
    }

    public function update(Request $request, Employee $employee)
    {
        $this->ensureEmailDoesntBelongToOtherContact($employee->contact, $request->email);

        $employee->load('contact.user');

        $contact = $employee->contact;

        $user = $contact?->user ?? User::whereEmail($request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('user.user')])]);
        }

        if (Contact::whereUserId($user->id)->where('id', '!=', $employee->contact_id)->exists()) {
            throw ValidationException::withMessages(['message' => 'contact.login.email_belongs_to_team_member']);
        }

        \DB::beginTransaction();

        $contact->user_id = $user->id;
        $contact->save();

        if (auth()->user()->canAny(['user:change-role', 'user:edit'])) {
            \DB::table('model_has_roles')->whereModelType('User')->whereModelId($user->id)->whereTeamId(auth()->user()?->current_team_id)->delete();

            $user->assignRole(SpatieRole::find($request->role_ids));
        }

        if ($request->force_change_password) {
            $user->password = bcrypt($request->password);
            $user->save();
        }

        \DB::commit();
    }

    public function updateCurrentPeriod(Request $request, Employee $employee)
    {
        $user = $employee->contact?->user;

        if (! $user) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('user.user')])]);
        }

        $period = Period::query()
            ->byTeam()
            ->where('id', $request->period_id)
            ->first();

        if (! $period) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.period.period')])]);
        }

        $preference = $user->preference;
        $preference['academic']['period_id'] = $request->period_id;
        $user->preference = $preference;
        $user->save();
    }
}
