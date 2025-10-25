<?php

namespace App\Services\Reception;

use App\Enums\OptionType;
use App\Enums\QualificationResult;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Reception\EnquiryQualificationResource;
use App\Models\Contact;
use App\Models\Option;
use App\Models\Qualification;
use App\Models\Reception\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnquiryQualificationService
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

    public function findByUuidOrFail(Enquiry $enquiry, string $uuid): Qualification
    {
        return Qualification::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($enquiry) {
                    $q->whereId($enquiry->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('student.qualification.qualification'));
    }

    public function list(Request $request, Enquiry $enquiry): AnonymousResourceCollection
    {
        return EnquiryQualificationResource::collection($enquiry->contact->qualifications()->get());
    }

    public function create(Request $request, Enquiry $enquiry): Qualification
    {
        \DB::beginTransaction();

        $qualification = Qualification::forceCreate($this->formatParams($request, $enquiry));

        $enquiry->contact->qualifications()->save($qualification);

        $qualification->addMedia($request);

        \DB::commit();

        return $qualification;
    }

    private function formatParams(Request $request, Enquiry $enquiry, ?Qualification $qualification = null): array
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

    public function update(Request $request, Enquiry $enquiry, Qualification $qualification): void
    {
        \DB::beginTransaction();

        $qualification->forceFill($this->formatParams($request, $enquiry, $qualification))->save();

        $qualification->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Enquiry $enquiry, Qualification $qualification): void
    {
        //
    }
}
