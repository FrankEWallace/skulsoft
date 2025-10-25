<?php

namespace App\Services\Employee;

use App\Enums\VerificationStatus;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountActionService
{
    public function makePrimary(Request $request, Employee $employee, string $account): void
    {
        $account = Account::query()
            ->whereHasMorph(
                'accountable',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($account)
            ->getOrFail(trans('employee.account.account'));

        if ($account->is_primary) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $accounts = Account::query()
            ->where('accountable_id', $employee->contact_id)
            ->where('accountable_type', 'Contact')
            ->update([
                'is_primary' => false,
            ]);

        $account->is_primary = true;
        $account->save();
    }

    public function action(Request $request, Employee $employee, string $account): void
    {
        $request->validate([
            'status' => 'required|in:verify,reject',
            'comment' => 'required_if:status,reject|max:200',
        ]);

        $account = Account::query()
            ->whereHasMorph(
                'accountable',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($account)
            ->getOrFail(trans('employee.account.account'));

        if (! $account->getMeta('self_upload')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        if ($account->verification_status != VerificationStatus::PENDING) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }

        if ($request->status == 'reject') {
            $account->setMeta([
                'status' => 'rejected',
                'comment' => $request->comment,
            ]);
            $account->save();

            return;
        }

        $account->verified_at = now()->toDateTimeString();
        $account->setMeta([
            'verified_by' => auth()->user()?->name,
        ]);
        $account->save();
    }
}
