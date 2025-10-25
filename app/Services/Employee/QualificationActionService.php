<?php

namespace App\Services\Employee;

use App\Enums\VerificationStatus;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QualificationActionService
{
    public function action(Request $request, Employee $employee, string $qualification): void
    {
        $request->validate([
            'status' => 'required|in:verify,reject',
            'comment' => 'required_if:status,reject|max:200',
        ]);

        $qualification = Qualification::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($qualification)
            ->getOrFail(trans('employee.qualification.qualification'));

        if (! $qualification->getMeta('self_upload')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        if ($qualification->verification_status != VerificationStatus::PENDING) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }

        if ($request->status == 'reject') {
            $qualification->setMeta([
                'status' => 'rejected',
                'comment' => $request->comment,
            ]);
            $qualification->save();

            return;
        }

        $qualification->verified_at = now()->toDateTimeString();
        $qualification->setMeta([
            'verified_by' => auth()->user()?->name,
        ]);
        $qualification->save();
    }
}
