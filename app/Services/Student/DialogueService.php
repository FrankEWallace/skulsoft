<?php

namespace App\Services\Student;

use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Contact;
use App\Models\Dialogue;
use App\Models\Option;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DialogueService
{
    public function preRequisite(Request $request): array
    {
        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::STUDENT_DIALOGUE_CATEGORY->value)
            ->get());

        return compact('categories');
    }

    public function findByUuidOrFail(Student $student, string $uuid): Dialogue
    {
        return Dialogue::query()
            ->whereHasMorph(
                'model',
                [Contact::class],
                function ($q) use ($student) {
                    $q->whereId($student->contact_id);
                }
            )
            ->whereUuid($uuid)
            ->getOrFail(trans('student.dialogue.dialogue'));
    }

    public function create(Request $request, Student $student): Dialogue
    {
        \DB::beginTransaction();

        $dialogue = Dialogue::forceCreate($this->formatParams($request, $student));

        $student->contact->dialogues()->save($dialogue);

        $dialogue->addMedia($request);

        \DB::commit();

        return $dialogue;
    }

    private function formatParams(Request $request, Student $student, ?Dialogue $dialogue = null): array
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

    private function isEditable(Student $student, Dialogue $dialogue): void
    {
        if (! $dialogue->isEditable) {
            throw ValidationException::withMessages([
                'message' => trans('user.errors.permission_denied'),
            ]);
        }
    }

    public function update(Request $request, Student $student, Dialogue $dialogue): void
    {
        $this->isEditable($student, $dialogue);

        \DB::beginTransaction();

        $dialogue->forceFill($this->formatParams($request, $student, $dialogue))->save();

        $dialogue->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Student $student, Dialogue $dialogue): void
    {
        $this->isEditable($student, $dialogue);
    }
}
