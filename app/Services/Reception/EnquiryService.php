<?php

namespace App\Services\Reception;

use App\Actions\CreateContact;
use App\Actions\UpdateContact;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\FamilyRelation;
use App\Enums\Gender;
use App\Enums\Locality;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Enums\Reception\EnquiryNature;
use App\Enums\Reception\EnquiryStatus;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\CustomFieldResource;
use App\Http\Resources\OptionResource;
use App\Domain\Academic\Models\Period;
use App\Models\CustomField;
use App\Models\Guardian;
use App\Models\Option;
use App\Models\Reception\Enquiry;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EnquiryService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.reception.enquiry_number_prefix');
        $numberSuffix = config('config.reception.enquiry_number_suffix');
        $digit = config('config.reception.enquiry_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Enquiry::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $natures = EnquiryNature::getOptions();

        $relations = FamilyRelation::getOptions();

        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::ENQUIRY_TYPE->value)
            ->get());

        $sources = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::ENQUIRY_SOURCE->value)
            ->get());

        $stages = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::ENQUIRY_STAGE->value)
            ->get());

        $genders = Gender::getOptions();

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->orderBy('start_date', 'desc')
            ->get());

        $statuses = EnquiryStatus::getOptions();

        $castes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CASTE->value)
            ->get());

        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CATEGORY->value)
            ->get());

        $religions = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::RELIGION->value)
            ->get());

        $localities = Locality::getOptions();

        $bloodGroups = BloodGroup::getOptions();

        $maritalStatuses = MaritalStatus::getOptions();

        $customFields = CustomFieldResource::collection(CustomField::query()
            ->byTeam()
            ->whereForm(CustomFieldForm::ENQUIRY)
            ->orderBy('position')
            ->get());

        return compact('natures', 'relations', 'types', 'sources', 'stages', 'genders', 'periods', 'statuses', 'customFields', 'castes', 'categories', 'localities', 'bloodGroups', 'maritalStatuses', 'religions');
    }

    public function create(Request $request): Enquiry
    {
        \DB::beginTransaction();

        if ($request->nature == EnquiryNature::OTHER->value) {
        } else {
            $contact = (new CreateContact)->execute($request->all());

            $request->merge([
                'contact_id' => $contact->id,
            ]);
        }

        $enquiry = Enquiry::forceCreate($this->formatParams($request));

        (new UpdateContact)->execute($contact, [
            ...$request->all(),
            'category_id' => $request->category_id,
            'caste_id' => $request->caste_id,
        ]);

        $enquiry->addMedia($request);

        \DB::commit();

        return $enquiry;
    }

    private function formatParams(Request $request, ?Enquiry $enquiry = null): array
    {
        $formatted = [
            'period_id' => $request->period_id,
            'date' => $request->date,
            'stage_id' => $request->stage_id,
            'type_id' => $request->type_id,
            'source_id' => $request->source_id,
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'remarks' => $request->remarks,
            'description' => $request->description,
        ];

        if ($request->nature == EnquiryNature::ADMISSION->value) {
            $formatted['course_id'] = $request->course_id;
            $formatted['contact_id'] = $request->contact_id;
        }

        if (! $enquiry) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
            $formatted['status'] = EnquiryStatus::OPEN->value;
            $formatted['nature'] = $request->nature;
        }

        $meta = $request->meta ?? [];
        $meta['custom_fields'] = $request->custom_fields;

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Enquiry $enquiry): void
    {
        if (! $enquiry->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        \DB::beginTransaction();

        if ($enquiry->nature == EnquiryNature::ADMISSION) {
            (new UpdateContact)->execute($enquiry->contact, $request->all());
            $request->merge([
                'contact_id' => $enquiry->contact_id,
            ]);
        }

        $enquiry->forceFill($this->formatParams($request, $enquiry))->save();

        $enquiry->updateMedia($request);

        \DB::commit();
    }

    public function updateDetail(Request $request, Enquiry $enquiry): void
    {
        if (! $enquiry->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        \DB::beginTransaction();

        $contact = $enquiry->contact;
        $request->merge([
            'nature' => $enquiry->nature->value,
            'contact_id' => $contact->id,
        ]);

        if (in_array($request->tab, ['contact', 'basic'])) {
            (new UpdateContact)->execute($contact, $request->all());
        }

        if ($request->tab == 'basic') {
            $enquiry->forceFill($this->formatParams($request, $enquiry))->save();
        }

        if ($request->tab == 'guardian') {
            $this->updateGuardian($request, $enquiry);
        }

        \DB::commit();
    }

    private function updateGuardian(Request $request, Enquiry $enquiry): void
    {
        $contact = $enquiry->contact;

        foreach ($request->guardians as $index => $guardian) {
            $guardianType = Arr::get($guardian, 'guardian_type', 'new');

            if ($guardianType == 'new') {
                $newGuardianContact = (new CreateContact)->execute($guardian);

                $newGuardianContact->occupation = Arr::get($guardian, 'occupation');
                $newGuardianContact->save();

                if (Arr::get($guardian, 'relation') == 'father') {
                    $contact->father_name = $newGuardianContact->name;
                } elseif (Arr::get($guardian, 'relation') == 'mother') {
                    $contact->mother_name = $newGuardianContact->name;
                }
                $contact->save();
                $secondaryContactId = $newGuardianContact->id;
            } else {
                $existingGuardian = Guardian::query()
                    ->with('contact')
                    ->whereUuid(Arr::get($guardian, 'guardian'))
                    ->wherePrimaryContactId($contact->id)
                    ->first();

                if (! $existingGuardian) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('guardian.guardian')])]);
                }

                $existingGuardian->relation = Arr::get($guardian, 'relation');
                $existingGuardian->save();

                $existingGuardianContact = $existingGuardian->contact;
                (new UpdateContact)->execute($existingGuardianContact, [
                    'name' => Arr::get($guardian, 'name'),
                    'contact_number' => Arr::get($guardian, 'contact_number'),
                    'occupation' => Arr::get($guardian, 'occupation'),
                ]);

                $secondaryContactId = $existingGuardianContact->id;
            }

            $existingGuardian = Guardian::query()
                ->wherePrimaryContactId($contact->id)
                ->whereContactId($secondaryContactId)
                ->first();

            if ($existingGuardian) {
                if ($existingGuardian->relation == 'father') {
                    $contact->father_name = $existingGuardian->contact->name;
                } elseif ($existingGuardian->relation == 'mother') {
                    $contact->mother_name = $existingGuardian->contact->name;
                }
                $contact->save();
            } else {
                $guardianRelation = Guardian::firstOrCreate([
                    'primary_contact_id' => $request->contact_id,
                    'contact_id' => $secondaryContactId,
                    'relation' => Arr::get($guardian, 'relation'),
                ]);

                $guardianRelation->update([
                    'position' => $index + 1,
                ]);
            }
        }
    }

    public function deletable(Enquiry $enquiry, $validate = false): bool
    {
        if (! $enquiry->is_editable) {
            if ($validate) {
                return false;
            }

            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if ($enquiry->status != EnquiryStatus::OPEN) {
            if ($validate) {
                return false;
            }

            throw ValidationException::withMessages(['message' => trans('reception.enquiry.could_not_delete_if_closed')]);
        }

        return true;
    }

    private function findMultiple(Request $request): array
    {
        if ($request->boolean('global')) {
            $listService = new EnquiryListService;
            $uuids = $listService->getIds($request);
        } else {
            $uuids = is_array($request->uuids) ? $request->uuids : [];
        }

        if (! count($uuids)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.data')])]);
        }

        return $uuids;
    }

    public function deleteMultiple(Request $request): int
    {
        $uuids = $this->findMultiple($request);

        $enquiries = Enquiry::whereIn('uuid', $uuids)->get();

        $deletable = [];
        foreach ($enquiries as $enquiry) {
            if ($this->deletable($enquiry, true)) {
                $deletable[] = $enquiry->uuid;
            }
        }

        if (! count($deletable)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_delete_any', ['attribute' => trans('reception.enquiry.enquiry')])]);
        }

        Enquiry::whereIn('uuid', $deletable)->delete();

        return count($deletable);
    }
}
