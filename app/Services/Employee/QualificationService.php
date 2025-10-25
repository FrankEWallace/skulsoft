<?php

namespace App\Services\Employee;

use App\Enums\OptionType;
use App\Enums\QualificationResult;
use App\Enums\VerificationStatus;
use App\Http\Resources\OptionResource;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QualificationService
{
    public function preRequisite(Request $request): array
    {
        $levels = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::QUALIFICATION_LEVEL->value)
            ->get());

        $results = QualificationResult::getOptions();

        return compact('levels', 'results');
    }

    public function findByUuidOrFail(Employee $employee, string $uuid): Qualification
    {
        return Qualification::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.qualification.qualification'));
    }

    public function create(Request $request, Employee $employee): Qualification
    {
        \DB::beginTransaction();

        $qualification = Qualification::forceCreate($this->formatParams($request, $employee));

        $employee->contact->qualifications()->save($qualification);

        if ($employee->user_id == auth()->id()) {
            $qualification->setMeta(['self_upload' => true]);
            $qualification->save();
        }

        $qualification->addMedia($request);

        \DB::commit();

        return $qualification;
    }

    private function formatParams(Request $request, Employee $employee, ?Qualification $qualification = null): array
    {
        $formatted = [
            'level_id' => $request->level_id,
            'course' => $request->course,
            'institute' => $request->institute,
            'affiliated_to' => $request->affiliated_to,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'result' => $request->result,
        ];

        if (! $qualification) {
            //
        }

        return $formatted;
    }

    private function isEditable(Employee $employee, Qualification $qualification): void
    {
        if (! $qualification->getMeta('self_upload')) {
            if ($employee->user_id == auth()->id()) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }

            return;
        }

        if ($employee->user_id != auth()->id()) {
            throw ValidationException::withMessages(['message' => trans('employee.could_not_edit_self_service_upload')]);
        }

        if ($qualification->getMeta('status') == VerificationStatus::REJECTED->value) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if (empty($qualification->verified_at->value)) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
    }

    public function update(Request $request, Employee $employee, Qualification $qualification): void
    {
        $this->isEditable($employee, $qualification);

        \DB::beginTransaction();

        $qualification->forceFill($this->formatParams($request, $employee, $qualification))->save();

        $qualification->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Employee $employee, Qualification $qualification): void
    {
        $this->isEditable($employee, $qualification);
    }
}
