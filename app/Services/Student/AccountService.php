<?php

namespace App\Services\Student;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class AccountService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function findByUuidOrFail(Student $student, string $uuid): Account
    {
        return Account::query()
            ->whereHasMorph(
                'accountable',
                [Contact::class],
                function ($q) use ($student) {
                    $q->whereId($student->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('student.account.account'));
    }

    public function create(Request $request, Student $student): Account
    {
        \DB::beginTransaction();

        $account = Account::forceCreate($this->formatParams($request, $student));

        $student->contact->accounts()->save($account);

        $account->addMedia($request);

        \DB::commit();

        return $account;
    }

    private function formatParams(Request $request, Student $student, ?Account $account = null): array
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

    public function update(Request $request, Student $student, Account $account): void
    {
        \DB::beginTransaction();

        $account->forceFill($this->formatParams($request, $student, $account))->save();

        $account->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Student $student, Account $account): void
    {
        //
    }
}
