<?php

namespace App\Services\Academic;

use App\Domain\Academic\Models\ProgramType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProgramTypeService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function findByUuidOrFail(string $uuid): ProgramType
    {
        return ProgramType::query()
            ->byTeam()
            ->findByUuidOrFail($uuid, trans('academic.program_type.program_type'), 'message');
    }

    public function create(Request $request): ProgramType
    {
        \DB::beginTransaction();

        $programType = ProgramType::forceCreate($this->formatParams($request));

        \DB::commit();

        return $programType;
    }

    private function formatParams(Request $request, ?ProgramType $programType = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'shortcode' => $request->shortcode,
            'description' => $request->description,
        ];

        if (! $programType) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, ProgramType $programType): void
    {
        \DB::beginTransaction();

        $programType->forceFill($this->formatParams($request, $programType))->save();

        \DB::commit();
    }

    public function deletable(ProgramType $programType, $validate = false): ?bool
    {
        $programExists = \DB::table('programs')
            ->whereTypeId($programType->id)
            ->exists();

        if ($programExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.program_type.program_type'), 'dependency' => trans('academic.program.program')])]);
        }

        return true;
    }
}
