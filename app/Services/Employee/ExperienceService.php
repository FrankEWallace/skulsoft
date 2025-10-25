<?php

namespace App\Services\Employee;

use App\Enums\OptionType;
use App\Enums\VerificationStatus;
use App\Http\Resources\OptionResource;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Experience;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExperienceService
{
    public function preRequisite(Request $request): array
    {
        $employmentTypes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::EMPLOYMENT_TYPE->value)
            ->get());

        return compact('employmentTypes');
    }

    public function findByUuidOrFail(Employee $employee, string $uuid): Experience
    {
        return Experience::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.experience.experience'));
    }

    public function create(Request $request, Employee $employee): Experience
    {
        \DB::beginTransaction();

        $experience = Experience::forceCreate($this->formatParams($request, $employee));

        $employee->contact->experiences()->save($experience);

        if ($employee->user_id == auth()->id()) {
            $experience->setMeta(['self_upload' => true]);
            $experience->save();
        }

        $experience->addMedia($request);

        \DB::commit();

        return $experience;
    }

    private function formatParams(Request $request, Employee $employee, ?Experience $experience = null): array
    {
        $formatted = [
            'employment_type_id' => $request->employment_type_id,
            'headline' => $request->headline,
            'title' => $request->title,
            'organization_name' => $request->organization_name,
            'location' => $request->location,
            'job_profile' => $request->job_profile,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date ?: null,
        ];

        if (! $experience) {
            //
        }

        return $formatted;
    }

    private function isEditable(Employee $employee, Experience $experience): void
    {
        if (! $experience->getMeta('self_upload')) {
            if ($employee->user_id == auth()->id()) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }

            return;
        }

        if ($employee->user_id != auth()->id()) {
            throw ValidationException::withMessages(['message' => trans('employee.could_not_edit_self_service_upload')]);
        }

        if ($experience->getMeta('status') == VerificationStatus::REJECTED->value) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if (empty($experience->verified_at->value)) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
    }

    public function update(Request $request, Employee $employee, Experience $experience): void
    {
        $this->isEditable($employee, $experience);

        \DB::beginTransaction();

        $experience->forceFill($this->formatParams($request, $employee, $experience))->save();

        $experience->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Employee $employee, Experience $experience): void
    {
        $this->isEditable($employee, $experience);
    }
}
