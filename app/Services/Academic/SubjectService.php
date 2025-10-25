<?php

namespace App\Services\Academic;

use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Academic\Subject;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubjectService
{
    public function preRequisite(): array
    {
        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::SUBJECT_TYPE->value)
            ->get());

        return compact('types');
    }

    public function findByUuidOrFail(string $uuid): Subject
    {
        return Subject::query()
            ->byPeriod()
            ->findByUuidOrFail($uuid, trans('academic.subject.subject'), 'message');
    }

    public function create(Request $request): Subject
    {
        \DB::beginTransaction();

        $subject = Subject::forceCreate($this->formatParams($request));

        \DB::commit();

        return $subject;
    }

    private function formatParams(Request $request, ?Subject $subject = null): array
    {
        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'code' => $request->code,
            'shortcode' => $request->shortcode,
            'type_id' => $request?->type_id,
            'description' => $request->description,
        ];

        if (! $subject) {
            $formatted['position'] = $request->integer('position', 0);
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Subject $subject): void
    {
        \DB::beginTransaction();

        $subject->forceFill($this->formatParams($request, $subject))->save();

        \DB::commit();
    }

    public function deletable(Subject $subject, $validate = false): bool
    {
        $subjectRecordExists = \DB::table('subject_records')
            ->whereSubjectId($subject->id)
            ->exists();

        if ($subjectRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.subject.subject'), 'dependency' => trans('academic.subject.subject')])]);
        }

        $attendanceExists = \DB::table('student_attendances')
            ->whereSubjectId($subject->id)
            ->exists();

        if ($attendanceExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.subject.subject'), 'dependency' => trans('student.attendance.attendance')])]);
        }

        return true;
    }
}
