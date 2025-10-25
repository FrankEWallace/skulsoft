<?php

namespace App\Services\Academic;

use App\Models\Academic\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProgramActionService
{
    public function updateConfig(Request $request, Program $program): void
    {
        //
    }

    public function reorder(Request $request): void
    {
        $programs = $request->programs ?? [];

        $allPrograms = Program::query()
            ->byTeam()
            ->get();

        foreach ($programs as $index => $programItem) {
            $program = $allPrograms->firstWhere('uuid', Arr::get($programItem, 'uuid'));

            if (! $program) {
                continue;
            }

            $program->position = $index + 1;
            $program->save();
        }
    }
}
