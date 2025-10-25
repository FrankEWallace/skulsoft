<?php

namespace App\Services\Employee;

use App\Enums\VerificationStatus;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function findByUuidOrFail(Employee $employee, string $uuid): Account
    {
        return Account::query()
            ->whereHasMorph(
                'accountable',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.account.account'));
    }

    public function create(Request $request, Employee $employee): Account
    {
        \DB::beginTransaction();

        $account = Account::forceCreate($this->formatParams($request, $employee));

        $employee->contact->accounts()->save($account);

        $contact = $employee->contact;

        if (
            Account::query()
                ->where('accountable_id', $contact->id)
                ->where('accountable_type', 'Contact')
                ->count() == 1
        ) {
            $account->is_primary = true;
            $account->save();
        }

        if ($employee->user_id == auth()->id()) {
            $account->setMeta(['self_upload' => true]);
            $account->save();
        }

        $account->addMedia($request);

        \DB::commit();

        return $account;
    }

    private function formatParams(Request $request, Employee $employee, ?Account $account = null): array
    {
        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'number' => $request->number,
            'bank_details' => [
                'bank_name' => $request->bank_name,
                'branch_name' => $request->branch_name,
                'bank_code1' => $request->bank_code1,
                'bank_code2' => $request->bank_code2,
                'bank_code3' => $request->bank_code3,
            ],
        ];

        return $formatted;
    }

    private function isEditable(Employee $employee, Account $account): void
    {
        if (! $account->getMeta('self_upload')) {
            if ($employee->user_id == auth()->id()) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }

            return;
        }

        if ($employee->user_id != auth()->id()) {
            throw ValidationException::withMessages(['message' => trans('employee.could_not_edit_self_service_upload')]);
        }

        if ($account->getMeta('status') == VerificationStatus::REJECTED->value) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if (empty($account->verified_at->value)) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
    }

    public function update(Request $request, Employee $employee, Account $account): void
    {
        $this->isEditable($employee, $account);

        \DB::beginTransaction();

        $account->forceFill($this->formatParams($request, $employee, $account))->save();

        $account->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Employee $employee, Account $account): void
    {
        if ($account->is_primary) {
            throw ValidationException::withMessages(['message' => trans('employee.account.could_not_delete_primary_account')]);
        }

        $this->isEditable($employee, $account);
    }
}
