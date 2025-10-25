<?php

namespace App\Services\Student;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Http\Resources\Student\StudentResource;
use App\Models\Academic\Batch;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RollNumberService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    private function validateInput(Request $request): Batch
    {
        return Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');
    }

    public function fetch(Request $request)
    {
        $batch = $this->validateInput($request);

        $request->merge(['select_all' => true]);

        $students = (new FetchBatchWiseStudent)->execute($request->all());

        return StudentResource::collection($students)
            ->additional([
                'meta' => [
                    'roll_number_prefix' => Arr::get($batch->config, 'roll_number_prefix'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        $batch = $this->validateInput($request);

        $request->merge(['select_all' => true]);

        $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

        if (array_diff(Arr::pluck($request->students, 'uuid'), Arr::pluck($students, 'uuid'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        foreach ($request->students as $index => $input) {
            $number = Arr::get($input, 'number') ?: null;

            $student = Student::where('uuid', Arr::get($input, 'uuid'))->first();
            $student->number = $number;
            $student->roll_number = $number ? (Arr::get($batch->config, 'roll_number_prefix').$number) : null;
            $student->save();
        }
    }
}
