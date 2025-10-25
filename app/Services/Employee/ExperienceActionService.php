<?php

namespace App\Services\Employee;

use App\Enums\VerificationStatus;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExperienceActionService
{
    public function action(Request $request, Employee $employee, string $experience): void
    {
        $request->validate([
            'status' => 'required|in:verify,reject',
            'comment' => 'required_if:status,reject|max:200',
        ]);

        $experience = Experience::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($experience)
            ->getOrFail(trans('employee.experience.experience'));

        if (! $experience->getMeta('self_upload')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        if ($experience->verification_status != VerificationStatus::PENDING) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }

        if ($request->status == 'reject') {
            $experience->setMeta([
                'status' => 'rejected',
                'comment' => $request->comment,
            ]);
            $experience->save();

            return;
        }

        $experience->verified_at = now()->toDateTimeString();
        $experience->setMeta([
            'verified_by' => auth()->user()?->name,
        ]);
        $experience->save();
    }
}
