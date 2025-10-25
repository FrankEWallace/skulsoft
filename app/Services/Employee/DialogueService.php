<?php

namespace App\Services\Employee;

use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Contact;
use App\Models\Dialogue;
use App\Models\Employee\Employee;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DialogueService
{
    public function preRequisite(Request $request): array
    {
        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::EMPLOYEE_DIALOGUE_CATEGORY->value)
            ->get());

        return compact('categories');
    }

    public function findByUuidOrFail(Employee $employee, string $uuid): Dialogue
    {
        return Dialogue::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.dialogue.dialogue'));
    }

    public function create(Request $request, Employee $employee): Dialogue
    {
        \DB::beginTransaction();

        $dialogue = Dialogue::forceCreate($this->formatParams($request, $employee));

        $employee->contact->dialogues()->save($dialogue);

        $dialogue->addMedia($request);

        \DB::commit();

        return $dialogue;
    }

    private function formatParams(Request $request, Employee $employee, ?Dialogue $dialogue = null): array
    {
        $formatted = [
            'category_id' => $request->category_id,
            'title' => $request->title,
            'date' => $request->date ?? today()->toDateString(),
            'description' => $request->description,
        ];

        if (! $dialogue) {
            $formatted['user_id'] = auth()->id();
        }

        return $formatted;
    }

    private function isEditable(Employee $employee, Dialogue $dialogue): void
    {
        if (! $dialogue->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function update(Request $request, Employee $employee, Dialogue $dialogue): void
    {
        $this->isEditable($employee, $dialogue);

        \DB::beginTransaction();

        $dialogue->forceFill($this->formatParams($request, $employee, $dialogue))->save();

        $dialogue->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Employee $employee, Dialogue $dialogue): void
    {
        $this->isEditable($employee, $dialogue);
    }
}
