<?php

namespace App\Services\Recruitment;

use App\Actions\CreateContact;
use App\Actions\UpdateContact;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Http\Resources\Employee\DesignationResource;
use App\Http\Resources\OptionResource;
use App\Models\Employee\Designation;
use App\Models\Option;
use App\Models\Recruitment\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function preRequisite(Request $request): array
    {
        $genders = Gender::getOptions();

        $maritalStatuses = MaritalStatus::getOptions();

        $bloodGroups = BloodGroup::getOptions();

        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::MEMBER_CATEGORY)
            ->get());

        $castes = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::MEMBER_CASTE)
            ->get());

        $religions = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::RELIGION)
            ->get());

        $designations = DesignationResource::collection(Designation::query()
            ->byTeam()
            ->get());

        return compact('genders', 'maritalStatuses', 'bloodGroups', 'categories', 'castes', 'religions', 'designations');
    }

    public function create(Request $request): Application
    {
        \DB::beginTransaction();

        $contact = (new CreateContact)->execute($request->all());

        $request->merge([
            'contact_id' => $contact->id,
        ]);

        (new UpdateContact)->execute($contact, [
            ...$request->all(),
        ]);

        $application = Application::forceCreate($this->formatParams($request));

        $application->addMedia($request);

        \DB::commit();

        return $application;
    }

    private function formatParams(Request $request, ?Application $application = null): array
    {
        $formatted = [
            'designation_id' => $request->designation_id,
            'qualification_summary' => $request->qualification_summary,
            'application_date' => $request->application_date,
            'availability_date' => $request->availability_date,
        ];

        if (! $application) {
            $formatted['contact_id'] = $request->contact_id;
        }

        $meta = $application?->meta ?? [];

        $meta['is_manual'] = true;

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Application $application): void
    {
        \DB::beginTransaction();

        (new UpdateContact)->execute($application->contact, [
            ...$request->all(),
        ]);

        $application->forceFill($this->formatParams($request, $application))->save();

        $application->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Application $application): void
    {
        if (! $application->getMeta('is_manual')) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }
}
