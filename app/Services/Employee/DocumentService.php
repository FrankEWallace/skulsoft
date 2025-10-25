<?php

namespace App\Services\Employee;

use App\Enums\OptionType;
use App\Enums\VerificationStatus;
use App\Http\Resources\OptionResource;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Employee\Employee;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public function preRequisite(Request $request): array
    {
        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::EMPLOYEE_DOCUMENT_TYPE->value)
            ->get());

        return compact('types');
    }

    public function findByUuidOrFail(Employee $employee, string $uuid): Document
    {
        return Document::query()
            ->whereHasMorph(
                'documentable',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.document.document'));
    }

    public function create(Request $request, Employee $employee): Document
    {
        \DB::beginTransaction();

        $document = Document::forceCreate($this->formatParams($request, $employee));

        $employee->contact->documents()->save($document);

        if ($employee->user_id == auth()->id()) {
            $document->setMeta(['self_upload' => true]);
            $document->save();
        }

        $document->addMedia($request);

        \DB::commit();

        return $document;
    }

    private function formatParams(Request $request, Employee $employee, ?Document $document = null): array
    {
        $formatted = [
            'type_id' => $request->type_id,
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date ?: null,
            'description' => $request->description,
        ];

        if (! $document) {
            //
        }

        return $formatted;
    }

    private function isEditable(Employee $employee, Document $document): void
    {
        if (! $document->getMeta('self_upload')) {
            if ($employee->user_id == auth()->id()) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }

            return;
        }

        if ($employee->user_id != auth()->id()) {
            throw ValidationException::withMessages(['message' => trans('employee.could_not_edit_self_service_upload')]);
        }

        if ($document->getMeta('status') == VerificationStatus::REJECTED->value) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if (empty($document->verified_at->value)) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
    }

    public function update(Request $request, Employee $employee, Document $document): void
    {
        $this->isEditable($employee, $document);

        \DB::beginTransaction();

        $document->forceFill($this->formatParams($request, $employee, $document))->save();

        $document->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Employee $employee, Document $document): void
    {
        $this->isEditable($employee, $document);
    }
}
