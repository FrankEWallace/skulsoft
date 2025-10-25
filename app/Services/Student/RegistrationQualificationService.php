<?php

namespace App\Services\Student;

use App\Enums\OptionType;
use App\Enums\QualificationResult;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\RegistrationQualificationResource;
use App\Models\Contact;
use App\Models\Option;
use App\Models\Qualification;
use App\Models\Student\Registration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegistrationQualificationService
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

    public function findByUuidOrFail(Registration $registration, string $uuid): Qualification
    {
        return Qualification::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($registration) {
                    $q->whereId($registration->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('student.qualification.qualification'));
    }

    public function list(Request $request, Registration $registration): AnonymousResourceCollection
    {
        return RegistrationQualificationResource::collection($registration->contact->qualifications()->get());
    }

    public function create(Request $request, Registration $registration): Qualification
    {
        \DB::beginTransaction();

        $qualification = Qualification::forceCreate($this->formatParams($request, $registration));

        $registration->contact->qualifications()->save($qualification);

        $qualification->addMedia($request);

        \DB::commit();

        return $qualification;
    }

    private function formatParams(Request $request, Registration $registration, ?Qualification $qualification = null): array
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

        $meta = $qualification?->meta ?? [];

        $meta['session'] = $request->input('session');
        $meta['institute_address'] = $request->institute_address;
        $meta['total_marks'] = $request->total_marks;
        $meta['obtained_marks'] = $request->obtained_marks;
        $meta['percentage'] = $request->total_marks ? round($request->obtained_marks / $request->total_marks * 100, 2) : null;
        $meta['failed_subjects'] = $request->failed_subjects;

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Registration $registration, Qualification $qualification): void
    {
        \DB::beginTransaction();

        $qualification->forceFill($this->formatParams($request, $registration, $qualification))->save();

        $qualification->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Registration $registration, Qualification $qualification): void
    {
        //
    }
}
