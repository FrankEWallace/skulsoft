<?php

namespace App\Services\Student;

use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\RegistrationDocumentResource;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Option;
use App\Models\Student\Registration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegistrationDocumentService
{
    public function preRequisite(Request $request): array
    {
        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::STUDENT_DOCUMENT_TYPE->value)
            ->get());

        return compact('types');
    }

    public function findByUuidOrFail(Registration $registration, string $uuid): Document
    {
        return Document::query()
            ->whereHasMorph(
                'documentable',
                [Contact::class],
                function ($q) use ($registration) {
                    $q->whereId($registration->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('student.document.document'));
    }

    public function list(Request $request, Registration $registration): AnonymousResourceCollection
    {
        return RegistrationDocumentResource::collection($registration->contact->documents()->get());
    }

    public function create(Request $request, Registration $registration): Document
    {
        \DB::beginTransaction();

        $document = Document::forceCreate($this->formatParams($request, $registration));

        $registration->contact->documents()->save($document);

        $document->addMedia($request);

        \DB::commit();

        return $document;
    }

    private function formatParams(Request $request, Registration $registration, ?Document $document = null): array
    {
        $formatted = [
            'type_id' => $request->type_id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        if (! $document) {
            //
        }

        $meta = $document?->meta ?? [];

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Registration $registration, Document $document): void
    {
        \DB::beginTransaction();

        $document->forceFill($this->formatParams($request, $registration, $document))->save();

        $document->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Registration $registration, Document $document): void
    {
        //
    }
}
