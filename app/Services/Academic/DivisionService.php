<?php

namespace App\Services\Academic;

use App\Http\Resources\Academic\ProgramResource;
use App\Models\Academic\Division;
use App\Models\Academic\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DivisionService
{
    public function preRequisite(): array
    {
        $programs = ProgramResource::collection(Program::query()
            ->select('programs.*', 'academic_departments.name as department_name')
            ->leftJoin('academic_departments', 'academic_departments.id', '=', 'programs.department_id')
            ->byTeam()
            ->get());

        return compact('programs');
    }

    public function create(Request $request): Division
    {
        \DB::beginTransaction();

        $division = Division::forceCreate($this->formatParams($request));

        \DB::commit();

        return $division;
    }

    private function formatParams(Request $request, ?Division $division = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'program_id' => $request->program_id,
            'shortcode' => $request->shortcode,
            'description' => $request->description,
        ];

        $meta = $division?->meta ?? [];

        $meta['pg_account'] = $request->pg_account;

        if (! $division) {
            $formatted['position'] = $request->integer('position', 0);
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Division $division): void
    {
        \DB::beginTransaction();

        $division->forceFill($this->formatParams($request, $division))->save();

        \DB::commit();
    }

    public function deletable(Division $division, $validate = false): ?bool
    {
        // $courseExists = \DB::table('courses')
        //     ->whereDivisionId($division->id)
        //     ->exists();

        // if ($courseExists) {
        //     throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.division.division'), 'dependency' => trans('academic.course.course')])]);
        // }

        // $examTermExists = \DB::table('exam_terms')
        //     ->whereDivisionId($division->id)
        //     ->exists();

        // if ($examTermExists) {
        //     throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.division.division'), 'dependency' => trans('exam.term.term')])]);
        // }

        return true;
    }
}
